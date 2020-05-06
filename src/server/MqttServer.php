<?php
declare(strict_types=1);

namespace whaleFallWh\SwooleMqttServer\Server;

use Co\System;
use Swoole\Server;
use Swoole\Server\Task;
use Exception;
use whaleFallWh\SwooleMqttServer\Config;
use whaleFallWh\SwooleMqttServer\Libs\Redis\RedisPool;
use whaleFallWh\SwooleMqttServer\Server\Event\ReceiveEvent;
use whaleFallWh\SwooleMqttServer\Server\Message\MessageId;
use whaleFallWh\SwooleMqttServer\Server\Protocol\MQTT;
use whaleFallWh\SwooleMqttServer\Server\Subscribe\Subscribe;


class MqttServer {

    /** @var Server */
    private $server;

    public function __construct($config)
    {
        \Co::set(['hook_flags' => SWOOLE_HOOK_ALL | SWOOLE_HOOK_CURL]);
        $config['settings']['task_enable_coroutine'] = 1;
        $configInstance = Config::getInstance($config); unset($config);
        Subscribe::init();
            Subscribe::$subscribe::instance()->clearFds();
        $this->server = new Server($configInstance->get('host'), $configInstance->get('port'));
        $this->server->set($configInstance->get('settings'));
        $this->server->on('workerStart', [$this, 'onWorkerStart']);
        $this->server->on('receive', [$this, 'onReceive']);
        $this->server->on('task', [$this, 'onTask']);
        $this->server->on('finish', [$this, 'onFinish']);
        $this->server->on('close', [$this, 'onClose']);
        $this->server->on('shutdown', [$this, 'onShutdown']);
        MessageId::instance();  //Atomic需要在start之前实例化，否则多worker会有异常
        $this->server->start();
    }

    public function onWorkerStart(Server $server, int $workerId)
    {
        if ($workerId === 0) {
            MessageId::instance()->initMsgId();
        }
    }

    public function onTask(Server $server, Task $task)
    {
        $data = $task->data;
        $task->finish('success');
    }

    public function onFinish(Server $server, int $task_id, string $data)
    {

    }

    public function onReceive(Server $server, $fd, $reactor_id, $data)
    {
        $packet = MQTT::decode($data);
        switch ($packet['cmd']) {
            case MQTT::CMD_CONNECT:
                ReceiveEvent::onConnect($server, $fd, $reactor_id, $packet);
                break;
            case MQTT::CMD_PUBLISH:
                ReceiveEvent::onPublish($server, $fd, $reactor_id, $packet);
                break;
            case MQTT::CMD_PUBACK:
                ReceiveEvent::onPuback($server, $fd, $reactor_id, $packet);
                break;
            case MQTT::CMD_PUBREC:
                ReceiveEvent::onPubrec($server, $fd, $reactor_id, $packet);
                break;
            case MQTT::CMD_PUBREL:
                ReceiveEvent::onPubrel($server, $fd, $reactor_id, $packet);
                break;
            case MQTT::CMD_PUBCOMP:
                ReceiveEvent::onPubcomp($server, $fd, $reactor_id, $packet);
                break;
            case MQTT::CMD_SUBSCRIBE:
                ReceiveEvent::onSubscribe($server, $fd, $reactor_id, $packet);
                break;
            case MQTT::CMD_UNSUBSCRIBE:
                ReceiveEvent::onUnsubscribe($server, $fd, $reactor_id, $packet);
                break;
            case MQTT::CMD_PINGREQ:
                ReceiveEvent::onPingreq($server, $fd, $reactor_id, $packet);
                break;
            case MQTT::CMD_DISCONNECT:
                ReceiveEvent::onDisconnect($server, $fd, $reactor_id, $packet);
                break;
            default:
                throw new Exception('不合法的包\n');
                break;
        }
    }

    public function onClose(Server $server, $fd)
    {
        echo "连接断开: {$fd}" . PHP_EOL;
    }

    public function onShutdown(Server $server)
    {
        MessageId::instance()->saveMsgId();
    }
}
