<?php
declare(strict_types=1);

namespace whaleFallWh\SwooleMqttServer\Server\Event;

use Swoole\Server;
use whaleFallWh\SwooleMqttServer\Server\Message\MessageStore;
use whaleFallWh\SwooleMqttServer\Server\MqttServer;
use whaleFallWh\SwooleMqttServer\Server\Protocol\MQTT;
use whaleFallWh\SwooleMqttServer\SubscribeFds;

class ReceiveEvent
{

    /**
     * cmd = 1
     */
    public static function onConnect(Server $server, int $fd, int $reactor_id, array $packet)
    {
        $client_id = $packet['client_id'];
        echo "client_id($client_id) connect" . PHP_EOL;
        $server->send($fd, MQTT::encode(['cmd' => MQTT::CMD_CONNACK]));
    }

    /**
     * cmd = 3时 qos处理
     *
     * @param Server $server
     * @param int $fd
     * @param int $reactor_id
     * @param array $packet
     */
    public static function onPublish(Server $server, int $fd, int $reactor_id, array $packet)
    {
        $qos = $packet['qos'] ?? 0;
        $allSubFds = SubscribeFds::instance()->getSubsribeFbsByTopic($packet['topic']);
        foreach ($allSubFds as $subFd) {
            go(function () use ($server, $subFd, $packet){
                $messag_id = MqttServer::incrMessageId();
                if ($packet['qos'] !== 0) {
                    MessageStore::instance()->addMsgToStore($messag_id, $packet);
                }
                $server->send($subFd, MQTT::encode([
                    'cmd' => MQTT::CMD_PUBLISH,
                    'topic' => $packet['topic'],
                    'content' => $packet['content'],
                    'qos' => $packet['qos'],
                    'message_id' => $messag_id,
                ]));
            });
        }
        switch ($qos) {
            case 0:
                break;
            case 1:
                $server->send($fd, MQTT::encode([
                    'cmd' => MQTT::CMD_PUBACK,
                    'message_id' => $packet['message_id']
                ]));
                break;
            case 2:
                $server->send($fd, MQTT::encode([
                    'cmd' => MQTT::CMD_PUBREC,
                    'message_id' => $packet['message_id']
                ]));
                break;
        }
    }

    /**
     * cmd = 4
     *
     * @param Server $server
     * @param int $fd
     * @param int $reactor_id
     * @param array $packet
     */
    public static function onPuback(Server $server, int $fd, int $reactor_id, array $packet)
    {
        MessageStore::instance()->delMsgFromStore($packet['message_id']);
    }

    /**
     * cmd = 5
     *
     * @param Server $server
     * @param int $fd
     * @param int $reactor_id
     * @param array $packet
     */
    public static function onPubrec(Server $server, int $fd, int $reactor_id, array $packet)
    {
        $server->send($fd, MQTT::encode([
            'cmd' => MQTT::CMD_PUBREL,
            'message_id' => $packet['message_id']
        ]));
    }

    /**
     * cmd = 6
     *
     * @param Server $server
     * @param int $fd
     * @param int $reactor_id
     * @param array $packet
     */
    public static function onPubrel(Server $server, int $fd, int $reactor_id, array $packet)
    {
        $server->send($fd, MQTT::encode([
            'cmd' => MQTT::CMD_PUBCOMP,
            'message_id' => $packet['message_id']
        ]));
    }


    /**
     * cmd = 7
     *
     * @param Server $server
     * @param int $fd
     * @param int $reactor_id
     * @param array $packet
     */
    public static function onPubcomp(Server $server, int $fd, int $reactor_id, array $packet)
    {
        MessageStore::instance()->delMsgFromStore($packet['message_id']);
    }

    /**
     * cmd = 8
     *
     * @param Server $server
     * @param int $fd
     * @param int $reactor_id
     * @param array $packet
     */
    public static function onSubscribe(Server $server, int $fd, int $reactor_id, array $packet)
    {
        $topics = $packet['topics'];
        foreach ($topics as $topic=>$qos) {
            switch ($qos) {
                case 0:
                case 1:
                case 2:
                    $server->send($fd, MQTT::encode([
                        'cmd' => MQTT::CMD_SUBACK,
                        'codes' => [],
                        'message_id' => $packet['message_id'] ,
                    ]));
                    SubscribeFds::instance()->sub($topic, $fd);
            }
        }
    }

    /**
     * 10
     *
     * @param Server $server
     * @param int $fd
     * @param int $reactor_id
     * @param array $packet
     */
    public static function onUnsubscribe(Server $server, int $fd, int $reactor_id, array $packet)
    {
        $instance = SubscribeFds::instance();
        $topics = $packet['topics'];
        foreach ($topics as $topic) {
            $instance->unSub($topic, $fd);
        }
    }

    /**
     * cmd = 12
     *
     * @param Server $server
     * @param int $fd
     * @param int $reactor_id
     * @param array $packet
     */
    public static function onPingreq(Server $server, int $fd, int $reactor_id, array $packet)
    {
        $server->send($fd, MQTT::encode(['cmd' => MQTT::CMD_PINGRESP]));
    }

    /**
     * cmd = 14
     *
     * @param Server $server
     * @param int $fd
     * @param int $reactor_id
     * @param array $packet
     */
    public static function onDisconnect(Server $server, int $fd, int $reactor_id, array $packet)
    {
        //断开连接
    }

}