<?php

namespace App\Controllers;

use Throwable;
use App\Models\User;
use App\Services\UserService;

/**
 * Class UserController
 *
 * @package App\Controllers
 * @property-read UserService $userService
 */
class UserController extends Controller
{
    const REGISTER_KEY_PREFIX = 'sms:register:';
    const FORGET_PASSWORD_KEY_PREFIX = 'sms:forget:';

    public function showAction()
    {
        $name = 'guanxin';
        return $this->view->setVar('name', $name);
    }

    public function createAction()
    {
        $user_name = input('user_name', ['account']);
        $password = input('password');
        $real_name = input('real_name', '');
        $code = input('code');
        $mobile = input('mobile', ['regex' => '#^\d{11}$#']);

        if (User::exists(['user_name' => $user_name])) {
            return '用户已存在';
        }

        if (User::exists(['mobile' => $mobile])) {
            return '本手机已经注册账号';
        }
        $prefix = self::REGISTER_KEY_PREFIX;
        if ($this->redis->get($prefix . $mobile) !== $code) {
            return '验证码错误';
        }

        $connection = User::connection();
        $connection->begin();
        try {
            $user = new User();

            $user->user_name = $user_name;
            $user->status = User::STATUS_ACTIVE;
            $user->password = $password;
            $user->real_name = $real_name;
            $user->mobile = $mobile;
            $user->login_ip = client_ip();
            $user->login_time = time();

            $user->create();

            $connection->commit();
        } catch (Throwable $throwable) {
            $connection->rollback();

            $this->logger->error($throwable);
            return '注册失败，请稍后重试';
        }

        return ['user_name' => $user_name];
    }

    public function loginAction()
    {
        $user_name = input('user_name');
        $password = input('password');

        if (strlen($user_name) === 11 && preg_match('#^\d{11}$#', $user_name)) {
            $user = User::first(['mobile' => $user_name]);
        } else {
            $user = User::first(['user_name' => $user_name]);
        }

        if (!$user) {
            return '该用户不存在，请注册后在使用';
        }

        if (!$user->verifyPassword($password)) {
            return '账号密码不正确';
        }

        return $this->userService->login($user);
    }

    public function passwordAction()
    {
        $old_password = input('old_password');
        $new_password = input('new_password');
        $confirm_password = input('confirm_password');
        $user_id = $this->identity->getId();
        $user = User::get($user_id);
        if (!$user) {
            return '用户未登录';
        }
        if (!$user->verifyPassword($old_password)) {
            return '原始密码错误';
        }
        if ($new_password !== $confirm_password) {
            return '确认密码与新密码不一致';
        }

        $user->password = $new_password;

        if ($user->update()) {
            return $user->only(['user_id', 'user_name']);
        } else {
            return '密码修改失败';
        }
    }

    public function forgetPasswordAction()
    {
        $mobile = input('mobile', ['regex' => '#^\d{11}$#']);
        $code = input('code');
        $new_password = input('new_password');
        $confirm_password = input('confirm_password');
        if ($new_password !== $confirm_password) {
            return '确认密码与新密码不一致';
        }
        $prefix = self::FORGET_PASSWORD_KEY_PREFIX;
        if ($this->redis->get($prefix . $mobile) !== $code) {
            return '验证码错误';
        }
        $user = User::first(['mobile' => $mobile]);
        if ($user === null) {
            return '该用户未注册';
        }
        $user->password = $new_password;
        if ($user->update()) {
            return $user->only(['user_id', 'user_name']);
        } else {
            return '密码修改失败';
        }
    }

    public function smsAction()
    {
        $mobile = input('mobile', ['regex' => '#^\d{11}$#']);
        $type = input('type', 'register');

        if (!in_array($type, ['register', 'forget_password'], true)) {
            return '传入的类别参数错误';
        }

        if (User::first(['mobile' => $mobile]) !== null && $type === 'register') {
            return '用户已经注册了';
        }

        $templateCode = '';
        $prefix = '';
        if ($type === 'register') {
            $templateCode = 'SMS_181310422';
            $prefix = self::REGISTER_KEY_PREFIX;
        }

        if ($type === 'forget_password') {
            $templateCode = 'SMS_181211976';
            $prefix = self::FORGET_PASSWORD_KEY_PREFIX;
        }

        if ($this->redis->get($prefix . 'nums:' . $mobile) >= 3
            && $this->redis->get($prefix . 'firsttime:' . $mobile) !== false
        ) {
            return '你的验证码获取已达上限，请30分钟后再来获取';
        }

        $code = random_sms_code();

        if ($this->configure->env === 'prod') {
            $result = $this->aliyunSmsService->send($mobile, $templateCode, ['code' => $code]);
            if ($result['Code'] !== 'OK') {
                if ($result['Code'] == 'isv.BUSINESS_LIMIT_CONTROL') {
                    return '60秒内不能重复发送';
                }
                return '验证码发送失败';
            }
        } else {
            $code = '123456';
        }

        $this->redis->set($prefix . $mobile, $code, seconds('60s'));
        $sendNums = $this->redis->incr($prefix . 'nums:' . $mobile);
        if ($sendNums === 1) {
            $this->redis->expire($prefix . 'nums:' . $mobile, seconds('30m'));
            $this->redis->set($prefix . 'firsttime:' . $mobile, time(), seconds('30m'));
        }
        return ['Message' => '发送成功', 'Code' => $result['Code']];
    }
}
