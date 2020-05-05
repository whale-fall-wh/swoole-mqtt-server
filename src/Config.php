<?php
declare(strict_types=1);


namespace whaleFallWh\SwooleMqttServer;


class Config
{
    private static $instance;

    private static $config = [];

    private function __construct($config)
    {
        self::$config = $config;
    }

    public static function getInstance($config=[])
    {
        if (is_null(self::$instance)) {
            self::$instance = new self($config);
        }
        return self::$instance;
    }

    /**
     * @param $keys
     * @param null $default
     * @return null|mixed
     */
    public function get($key, $default = null)
    {
        if (in_array($key, array_keys(self::$config))) {
            return self::$config[$key];
        }
        return $default;
    }
}
