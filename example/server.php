<?php

require_once __DIR__.'/../vendor/autoload.php';
use whaleFallWh\SwooleMqttServer\Server\Subscribe\Subscribe;

$config = [
    'host' => '0.0.0.0',
    'port' => 9501,
    'username' => '',
    'password' => '',
    'callbacks' => [

    ],
    'settings' => [
        'worker_num' => 2,
        'open_mqtt_protocol' => true,
        'pid_file' => __DIR__ . '/../runtime/pid'
    ],
    'subscribe' => [
        'type' => Subscribe::TYPE_REDIS, 
        'host' => '127.0.0.1',
        'port' => 6379,
        'auth' => '123456',
        'dbIndex' => 15,
    ]
];

(new \whaleFallWh\SwooleMqttServer\Application($config)) -> run();