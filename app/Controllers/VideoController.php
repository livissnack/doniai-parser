<?php

namespace App\Controllers;

class VideoController extends Controller
{
    public function analyzeAction()
    {
        $parse_url = input('video_url', ['string', 'default' => '']);
        $host = env('DONIAI_API_HOST');
        $response = http_get($host.'/api/v1.0/analyze/douyin?url='.$parse_url)->body;
        if ($response['code'] === 200) {
            return $response['data'];
        }
        return '请求出错';
    }
}
