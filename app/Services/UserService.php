<?php

namespace App\Services;

use App\Models\User;
use ManaPHP\Service;

/**
 * Class UserService
 *
 * @package App\Services
 */
class UserService extends Service
{
    /**
     * @param \App\Models\User $user
     *
     * @return mixed
     * @throws \ManaPHP\Exception\InvalidValueException
     */
    public function login($user)
    {
        if ($user->status === User::STATUS_LOCKED) {
            return '您的账号已锁定，请联系我们';
        } elseif ($user->status !== User::STATUS_ACTIVE) {
            return '您的账号当前不可用';
        }

        if (time() - $user->login_time > 60) {
            $user->login_time = time();
            $user->login_ip = client_ip();

            $user->update();
        }

        $ttl = seconds('7d');
        $token = jwt_encode(['user_id' => $user->user_id, 'user_name' => $user->user_name], $ttl, 'user');

        return ['user_name' => $user->user_name, 'token' => $token, 'ttl' => $ttl - seconds('1d')];
    }
}