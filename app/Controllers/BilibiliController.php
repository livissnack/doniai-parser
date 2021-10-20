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
        $response = http_get('https://hiphup-api.herokuapp.com/api/v1.0/analyze/bilibili?url='.$parse_url)->body;
        if ($response['code'] === 200) {
            return $response['data'];
        }

        return '请求出错';
    }
}
