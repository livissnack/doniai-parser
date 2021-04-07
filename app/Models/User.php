<?php

namespace App\Models;

class User extends Model
{
    const STATUS_ACTIVE = 1;    //激活
    const STATUS_LOCKED = 2;    //锁定

    public $user_id;
    public $user_name;
    public $real_name;
    public $balance;
    public $status;
    public $salt;
    public $password;
    public $mobile;
    public $qq;
    public $email;
    public $ident_card;
    public $login_ip;
    public $login_time;
    public $updator_name;
    public $updated_time;
    public $creator_name;
    public $created_time;

    /**
     * @return string
     */
    public function getTable()
    {
        return 'user';
    }

    /**
     * @return string
     */
    public function getPrimaryKey()
    {
        return 'user_id';
    }

    /**
     * @param string $password
     *
     * @return string
     */
    protected function _hashPassword($password)
    {
        return md5(md5($password) . $this->salt);
    }

    /**
     * @param string $password
     *
     * @return bool
     */
    public function verifyPassword($password)
    {
        return $this->_hashPassword($password) === $this->password;
    }

    public function create()
    {
        $this->salt = bin2hex(random_bytes(8));
        $this->password = $this->_hashPassword($this->password);

        return parent::create();
    }

    public function update()
    {
        if ($this->hasChanged(['password'])) {
            $this->salt = bin2hex(random_bytes(8));
            $this->password = $this->_hashPassword($this->password);
        }

        return parent::update();
    }
}