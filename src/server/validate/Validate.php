<?php
declare(strict_types=1);

namespace whaleFallWh\SwooleMqttServer\Server\Validate;


use whaleFallWh\SwooleMqttServer\Config;

class Validate
{
    public static function checkAuth(string $username, string $password)
    {
        $username_config = Config::getInstance()->get('username');
        $password_config = Config::getInstance()->get('password');
        if ($username_config && $username_config != $username) {
            return false;
        }
        if ($password_config && $password_config != $password) {
            return false;
        }
        return true;
    }
}