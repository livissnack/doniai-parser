Vue.prototype.$axios = axios;
Vue.prototype.$moment = moment;
Vue.prototype.$qs = Qs;
Vue.prototype._ = _;
Vue.prototype.sessionStorage = window.sessionStorage;
Vue.prototype.localStorage = window.localStorage;
Vue.prototype.console = console;

CONTROLLER_URL = location.pathname.substr(BASE_URL.length);
if (CONTROLLER_URL.endsWith('/index')) {
    CONTROLLER_URL = CONTROLLER_URL.substr(0, CONTROLLER_URL.length - 6);
}

(function () {
    let urlKey = `last_url_query.${document.location.pathname}`;
    window.onbeforeunload = (e) => {
        sessionStorage.setItem(urlKey, document.location.search);
    };

    if (document.location.search !== '' || document.referrer === '') {
        return;
    }
    let last_url_query = sessionStorage.getItem(urlKey);
    window.history.replaceState(null, null, last_url_query === null ? '' : last_url_query);
}());


document.location.query = document.location.search !== '' ? Qs.parse(document.location.search.substr(1)) : {};

axios.defaults.baseURL = BASE_URL;
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

axios.interceptors.request.use(function (config) {
    if (typeof vm.loading == 'boolean') {
        vm.loading = true;
    }

    config.url += config.url.indexOf('?') === -1 ? '?ajax' : '&ajax';
    return config;
});

axios.interceptors.response.use(function (res) {
        if (typeof vm.loading === 'boolean') {
            vm.loading = false;
        }

        if (typeof res.data === 'string') {
            vm.$alert(res.data, '服务器错误', {customClass: 'error-response'});
        }

        for (let name in res.headers) {
            let value = res.headers[name];
            if (value.match(/^https?:\/\//)) {
                console.warn('*'.repeat(32) + ' ' + name + ': ' + value);
            }
        }

        return res;
    },
    function (error) {
        if (typeof vm.loading == 'boolean') {
            vm.loading = false;
        }

        console.log(error);
        if (error.response.status) {
            switch (error.response.status) {
                case 400:
                    alert(error.response.data.message);
                    break;
                case 401:
                    window.location.href = '/login';
                    break;
                default:
                    alert(error.response.message || '网络错误，请稍后重试: ' + error.response.status);
                    break;
            }
        } else {
            alert('网络错误，请稍后重试。');
        }
    });

Vue.prototype.ajax_get = function (url, data, success) {
    if (typeof data === 'function') {
        success = data;
        data = null;
    } else if (data) {
        url += (url.indexOf('?') === -1 ? '?' : '&') + Qs.stringify(data);
    }

    let cache_key = null;
    if (success && url.match(/\bcache=[12]\b/) && localStorage.getItem('axios.cache.enabled') !== '0') {
        cache_key = 'axios.cache.' + url;
        let cache_value = sessionStorage.getItem(cache_key);
        if (cache_value) {
            success.bind(this)(JSON.parse(cache_value));
            return;
        }
    }

    return this.$axios.get(url).then((res) => {
        if (res.data.code === 0) {
            if (success) {
                if (cache_key) {
                    sessionStorage.setItem(cache_key, JSON.stringify(res.data.data));
                }
                success.bind(this)(res.data.data);
            }
        } else if (res.data.message) {
            this.$alert(res.data.message);
        }
        return res;
    });
};

Vue.prototype.ajax_post = function (url, data, success) {
    if (typeof data === 'function') {
        success = data;
        data = {};
    }

    let config = {};
    if (data instanceof FormData) {
        config.headers = {'Content-Type': 'multipart/form-data'};
    }

    return this.$axios.post(url, data, config).then((res) => {
        if (res.data.code === 0 && success) {
            success.bind(this)(res.data.data);
        }

        if (res.data.message !== '') {
            this.$buefy.snackbar.open({
                message: res.data.message,
                type: 'is-warning',
                position: 'is-bottom-right',
                actionText: 'MSG'
            })
        }
        return res
    });
};

Vue.filter('date', function (value, format = 'YYYY-MM-DD HH:mm:ss') {
    return value ? moment(value * 1000).format(format) : '';
});

Vue.filter('json', function (value) {
    return JSON.stringify(typeof value === 'string' ? JSON.parse(value) : value, null, 2);
});

Vue.prototype.format_date = function (value) {
    return value ? this.$moment(value * 1000).format('YYYY-MM-DD HH:mm:ss') : '';
};

Vue.prototype.auto_reload = function () {
    if (this.request && this.response) {
        let qs = this.$qs.parse(document.location.query);
        for (let k in qs) {
            this.$set(this.request, k, qs[k]);
        }

        this.reload().then(() => this.$watch('request', _.debounce(() => this.reload(), 500), {deep: true}));
    }
};

Vue.prototype.extract_ill = function (data) {
    if (!Array.isArray(data)) {
        return [];
    } else if (!data || data.length === 0) {
        return [];
    } else if (typeof data[0] === 'object') {
        return data.map(v => {
            let [id, label] = Object.values(v);
            return {id, label};
        });
    } else {
        return data.map(v => ({id: v, label: v}));
    }
};

App = Vue.extend({
    data() {
        return {
            topic: '',
            label: {
                id: 'ID',
                admin_id: '用户ID',
                admin_name: '用户名',
                created_time: '创建时间',
                updated_time: '更新时间',
                creator_name: '创建者',
                updator_name: '更新者',
                data: '数据',
                client_ip: '客户端IP',
                display_order: '排序',
                display_name: '显示名称',
                password: '密码',
                white_ip: 'IP白名单',
                status: '状态',
                email: '邮箱',
                tag: 'Tag',
                icon: '图标',
                path: '路径',
            },
            createVisible: false,
            editVisible: false,
            detailVisible: false,
            imageDialogVisible: false,
            uploadUrl: '',
            upload_image: '',
            uploaded: false,
            detail: {},
        }
    },
    methods: {
        reload() {
            if (!this.request || !this.response) {
                alert('bad reload');
            }

            let qs = this.$qs.stringify(this.request);
            window.history.replaceState(null, null, qs ? ('?' + qs) : '');
            document.location.query = document.location.search !== '' ? Qs.parse(document.location.search.substr(1)) : {};
            this.response = [];
            return this.$axios.get(document.location.href).then((res) => {
                if (res.data.code !== 0) {
                    this.$alert(res.data.message);
                } else {
                    this.response = res.data.data;
                }
            });
        },
        fDate(row, column, value) {
            return this.format_date(value);
        },

        fEnabled(row, column, value) {
            return ['禁用', '启用'][value];
        },
        picPreview(imgUrl){
            return [imgUrl];
        },
        handlePictureCardPreview(file) {
            this.imageDialogUrl = file.url;
            this.imageDialogVisible = true;
        },
        handleUploadSuccess(res) {
            this.create.upload_image = res.data.url;
            this.edit.upload_image = res.data.url;
            this.uploaded = true;
        },
        getUploadUrl() {
            this.ajax_get(CONTROLLER_URL + '/getUploadUrl', function (res) {
                this.uploadUrl = res;
            })
        },
        beforeUpload(file) {
            if (file.type !== 'image/png') {
                this.$alert('仅支持png图片类型！');
                return false;
            } else if (file.size > 1024000) {
                this.$alert('图片大小不能超过1000k');
                return false;
            }
        },
        do_create(create) {
            let success = true;
            if (typeof create === 'string') {
                this.$refs[create].validate(valid => success = valid);
            }
            success && this.ajax_post(CONTROLLER_URL + "/create", this.create, (res) => {
                this.createVisible = false;
                this.$refs.create.resetFields();
                this.reload();
            });
        },
        show_edit(row, overwrite = {}) {
            if (Object.keys(this.edit).length === 0) {
                this.edit = Object.assign({}, row, overwrite);
            } else {
                for (let key in this.edit) {
                    if (row.hasOwnProperty(key)) {
                        this.edit[key] = row[key];
                    }
                }
                for (let key in overwrite) {
                    this.edit[key] = overwrite[key]
                }
            }

            this.editVisible = true;
        },
        do_edit() {
            this.ajax_post(CONTROLLER_URL + "/edit", this.edit, () => {
                this.editVisible = false;
                this.reload();
            });
        },
        show_detail(row, action) {
            this.detailVisible = true;

            let key = Object.keys(row)[0];
            this.ajax_get(CONTROLLER_URL + '/' + (action ? action : "detail"), {[key]: row[key]}, (res) => {
                this.detail = res;
            });
        },
        do_delete(row, name = '') {
            let keys = Object.keys(row);
            let key = keys[0];

            if (!name) {
                name = (keys[1] && keys[1].indexOf('_name')) ? row[keys[1]] : row[key];
            }

            if (window.event.ctrlKey) {
                this.ajax_post(CONTROLLER_URL + "/delete", {[key]: row[key]}, () => this.reload());
            } else {
                this.$confirm('确认删除 `' + (name ? name : row[key]) + '` ?').then(() => {
                    this.ajax_post(CONTROLLER_URL + "/delete", {[key]: row[key]}, () => this.reload());
                });
            }
        },
        do_enable(row) {
            let key = Object.keys(row)[0];
            this.ajax_post(CONTROLLER_URL + "/enable", {[key]: row[key]}, () => row.enabled = 1);
        },
        do_disable(row) {
            let key = Object.keys(row)[0];
            this.ajax_post(CONTROLLER_URL + "/disable", {[key]: row[key]}, () => row.enabled = 0);
        },
        do_change_status(status, row) {
            current_status = status == 0?1:0;
            status_name = status == 0?"开启":"关闭";
            let key = Object.keys(row)[0];
            this.$confirm('确认'+status_name+'?').then(() => {
                this.ajax_post(CONTROLLER_URL + "/changeStatus", {[key]: row[key], current_status: current_status}, () => {
                    row.current_status = current_status;
                    console.log(row);
                });
            }).catch(()=>{console.log('已取消')});
        },
    },
});
