<?php

namespace App\Controllers;

use App\Models\Shop;

class ApiController extends Controller
{
    public function indexAction()
    {
        return Shop::select(['shop_id', 'name', 'image', 'price', 'sale_nums', 'nums', 'mode'])->where(['is_up' => Shop::UP])->all();
    }

    public function liveAction()
    {
        $url = input('url', ['string', 'default' => '']);
        $host = param_get('parse_url');
        $response = http_get($host.'?url='.$url)->body;
        if ($response['status']) {
            return $response;
        }
        return '解析失败';
    }
}
