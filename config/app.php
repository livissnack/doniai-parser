<?php

return [
    'id'         => 'parser',
    'name'         => 'Doniai短视频解析',
    'env'        => env('APP_ENV', 'prod'),
    'debug'      => env('APP_DEBUG', false),
    'version'    => '1.1.1',
    'timezone'   => 'PRC',
    'master_key' => env('MASTER_KEY'),
    'params'     => ['manaphp_brand_show' => 1, 'parse_url' => env('PARSE_API_URL')],
    'aliases'    => [
    ],
    'components' => [
        'httpServer' => [
            'port'                  => 9501,
            'worker_num'            => 2,
            'max_request'           => 1000000,
            'enable_static_handler' => true
        ],
        'db'         => env('DB_URL'),
        'redis'      => env('REDIS_URL'),
        'logger'     => ['level' => env('LOGGER_LEVEL', 'info')],
        'translator' => ['locale', env('locale', 'en'), 'dir' => path('')],
        'mailer'     => env('MAILER_URL'),
    ],
    'services'   => [],
    'plugins'    => [
        //'debugger',
        'logger',
    ],
    'tracers'    => env('APP_TRACERS', []),
];