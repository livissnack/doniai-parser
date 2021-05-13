<?php
namespace Livissnack\AliSms;

class Mailer implements MailerInterface
{
    private $secret_key;
    private $common_params;

    public function __construct($secret_key)
    {
        static $config;
        if (!$config) {
            $config = require_once('config/mail.php');
        }
        $this->common_params = $config;
        $this->secret_key = $secret_key;
    }

    public function send($message='a')
    {
        return $this->common_params;
    }

    public function body()
    {
        
    }

    public function sign($params)
    {
        ksort($params);
        $accessKeySecret = $this->secret_key;
        $stringToSign = 'GET&%2F&' . urlencode(http_build_query($params, null, '&', PHP_QUERY_RFC3986));
        return base64_encode(hash_hmac('sha1', $stringToSign, $accessKeySecret . '&', true));
    }
}