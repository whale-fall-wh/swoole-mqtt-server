<?php

namespace whaleFallWh\SwooleMqttServer;


(new MqttServer('0.0.0.0', 9501))->start(
    array(
        'open_mqtt_protocol' => 1,
        'worker_num' => 1,
        'task_worker_num' => 4,
        'log_file' => __DIR__.'/../runtime/log/swoole.log',
        'log_level' => SWOOLE_LOG_INFO,
    )
);
