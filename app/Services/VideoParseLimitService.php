<?php

namespace App\Services;

use ManaPHP\Service;

/**
 * Class VideoParseLimitService
 * @package App\Services
 */
class VideoParseLimitService extends Service
{
    protected $_cache_key = 'request::records';

    public function limit($ip, $limit = 1)
    {
        $redis = container('redis');
        $cache_key = $this->_cache_key.':'.ip2long($ip);
        $count = $redis->get($cache_key);
        if ($count >= $limit) {
            return '每天最多只能解析'.$limit.'次';
        }
        if ($count) {
            $redis->set($cache_key, (int)$count + 1, seconds('1d'));
        } else {
            $redis->set($cache_key, 1, seconds('1d'));
        }
        return true;
    }
}