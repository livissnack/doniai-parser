<?php

namespace App\Controllers;

use App\Services\VideoParseLimitService;

/**
 * Class VideoController
 *
 * @property-read VideoParseLimitService                                 $videoParseLimitService
 * @package App\Controllers
 */
class BilibiliController extends Controller
{
    public function indexAction()
    {
        return 0;
    }

    public function analyzeAction()
    {
        $parse_url = input('video_url', ['string', 'default' => '']);
        $domain = getDomain($parse_url);
        if (!strstr($domain, 'bilibili')) {
            return '解析地址不是Bilibili站点的';
        }
        $host = env('DONIAI_API_HOST');
        $response = http_get($host.'/api/v1.0/analyze/bilibili?url='.$parse_url)->body;
        if ($response['code'] === 200) {
            return $response['data'];
        }

        return '请求出错';
    }
}
