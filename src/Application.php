<?php
declare(strict_types=1);


namespace whaleFallWh\SwooleMqttServer;


use whaleFallWh\SwooleMqttServer\Server\MqttServer;

class Application
{

    private $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    private static function welcome()
    {

    }

    public function run()
    {
        self::welcome();
        new MqttServer($this->config);
    }
}