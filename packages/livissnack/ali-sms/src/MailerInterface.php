<?php
namespace Livissnack\AliSms;

interface MailerInterface
{
    public function send($message);

    public function sign($params);
}