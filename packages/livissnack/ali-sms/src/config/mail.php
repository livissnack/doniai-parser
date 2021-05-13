<?php
namespace Livissnack\AliSms\Config;

return [
    'default' => env('MAIL_ALI_SERVER', 'server_hangzhou'),
    'mailers' => [
        'server_hangzhou' => [
            'RegionId'          => env('MAIL_ALI_REGION_ID', 'cn-hangzhou'),
            'Host'              => env('MAIL_ALI_HOST', 'dm.aliyuncs.com'),
            'Version'           => env('MAIL_ALI_VERSION', '2015-11-23'),
        ],
        'server_singapore' => [
            'RegionId'          => env('MAIL_ALI_REGION_ID', 'ap-southeast-1'),
            'Host'              => env('MAIL_ALI_HOST', 'dm.ap-southeast-1.aliyuncs.com'),
            'Version'           => env('MAIL_ALI_VERSION', '2017-06-22'),
        ],
        'server_sydney' => [
            'RegionId'          => env('MAIL_ALI_REGION_ID', 'ap-southeast-2'),
            'Host'              => env('MAIL_ALI_HOST', 'dm.ap-southeast-2.aliyuncs.com'),
            'Version'           => env('MAIL_ALI_VERSION', '2017-06-22'),
        ]
    ],
    'common' => [
        'Format' => 'JSON',
        'SignatureMethod' => 'HMAC-SHA1',
        'SignatureVersion' => '1.0',
        'Timestamp' => time(),
        'SignatureNonce' => \ManaPHP\Helper\Uuid::v4(),
    ]
];