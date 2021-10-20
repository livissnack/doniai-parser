<?php

namespace App\Controllers;

class BilibiliController extends Controller
{
    public function indexAction()
    {
        return 0;
    }

    public function analyzeAction()
    {
        set_time_limit(0);
        $parse_url = input('video_url', ['string', 'default' => '']);
        $host = env('DONIAI_API_HOST');
        $response = http_get($host.'/api/v1.0/analyze/bilibili?url='.$parse_url)->body;
        if ($response['code'] === 200) {
            return $response['data'];
        }

        return '请求出错';
    }
}
