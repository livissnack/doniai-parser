<?php

namespace App\Controllers;

class VideoController extends Controller
{
    protected $_request_key = 'request::records';

    protected $_max_count = 20;

    public function analyzeAction()
    {
        $ip = $this->request->getClientIp();
        $cache_key = $this->_request_key.':'.ip2long($ip);
        $count = $this->redisDb->hGet($cache_key, $ip);
        if ($count >= $this->_max_count) {
            return '每天最多只能解析5次';
        }
        if ($count) {
            $this->redisDb->hIncrBy($cache_key, $ip, 1);
        } else {
            $this->redisDb->hSet($cache_key, $ip, 1);
            if ($this->redisDb->ttl($cache_key) < 0) {
                $this->redisDb->expire($cache_key, seconds('1d'));
            }
        }

        $parse_url = input('video_url', ['string', 'default' => '']);
        $host = env('DONIAI_API_HOST');
        $response = http_get($host.'/api/v1.0/analyze/douyin?url='.$parse_url)->body;
        if ($response['code'] === 200) {
            $target_url = $response['data']['video_url'];
            $response1 = http_get($host.'/api/v1.0/util/douyin?url='.$target_url)->body;
            if ($response1['code'] === 200) {
                $target_url = $response1['data']['video_url'];
            }
            return [
                'video_url' => preg_replace("/^http:/i", "https:", $target_url),
                'music_url' => $response['data']['music_url'],
                'cover_url' => $response['data']['cover_url'],
                'desc' => $response['data']['desc'],
            ];
        }

        return '请求出错';
    }

    public function xiguaAction()
    {
        $arr = [1, 43, 54, 62, 21, 66, 32, 78, 36, 76, 39];

        return quickSort($arr);
    }
}
