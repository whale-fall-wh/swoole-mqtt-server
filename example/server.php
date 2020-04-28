<?php

require_once __DIR__.'/../vendor/autoload.php';

$config = [
    'host' => '0.0.0.0',
    'port' => 9501,
    'username' => '',
    'password' => '',
    'callbacks' => [

    ],
    'settings' => [
        'worker_num' => 1,
        'open_mqtt_protocol' => true,
    ]
];

(new \whaleFallWh\SwooleMqttServer\Application($config)) -> run();