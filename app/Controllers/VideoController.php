<?php

namespace App\Controllers;

use App\Services\VideoParseLimitService;

/**
 * Class VideoController
 *
 * @property-read VideoParseLimitService                                 $videoParseLimitService
 * @package App\Controllers
 */
class VideoController extends Controller
{
    public function analyzeAction()
    {
        $parse_url = input('video_url', ['string', 'default' => '']);
        $domain = getDomain($parse_url);
        if (!strstr($domain, 'douyin')) {
            return '解析地址不是Douyin站点的';
        }
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
