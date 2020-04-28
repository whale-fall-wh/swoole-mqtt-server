<?php
declare(strict_types=1);

namespace whaleFallWh\SwooleMqttServer\Server;

use Swoole\Server;
use Exception;
use whaleFallWh\SwooleMqttServer\Server\Event\ReceiveEvent;
use whaleFallWh\SwooleMqttServer\Server\Protocol\MQTT;
use whaleFallWh\SwooleMqttServer\SubscribeFds;

class MqttServer {

    /** @var Server */
    private $server;

    /** @var int */
    protected static $_messageId = 1;

    public function __construct($config)
    {
        $this->server = new Server($config['host'], $config['port']);
        $this->server->set($config['settings']);
        $this->server->on('receive', [$this, 'onReceive']);
        $this->server->on('close', [$this, 'onClose']);
        $this->server->start();
    }

    public function onReceive(Server $server, $fd, $reactor_id, $data)
    {
        $packet = MQTT::decode($data);
//        var_dump($packet);
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

    /**
     * incrMessageId
     *
     * @return int
     */
    public static function incrMessageId()
    {
        $message_id = self::$_messageId++;
        if ($message_id >= 65535) {
            $self::$_messageId = 1;
        }
        return $message_id;
    }
}
