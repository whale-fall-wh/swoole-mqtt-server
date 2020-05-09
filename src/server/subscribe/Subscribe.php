<?php


namespace whaleFallWh\SwooleMqttServer\Server\Subscribe;


use whaleFallWh\SwooleMqttServer\Config;

class Subscribe
{
    const TYPE_MEM = 'mem';
    const TYPE_FILE = 'file';
    const TYPE_REDIS = 'redis';

    /** @var $subscribe SubscribeInterface */
    public static $subscribe;

    public static function init()
    {
        $config = Config::getInstance()->get('subscribe', []);
        $type = $config['type'] ?? self::TYPE_MEM;
        switch ($type) {
            case self::TYPE_MEM:
                self::$subscribe = MemSubscribeFds::class;
                break;
            case self::TYPE_FILE:
                self::$subscribe = FileSubscribeFds::class;
                break;
            case self::TYPE_REDIS:
                self::$subscribe = RedisSubscribeFds::class;
                break;
            default:
                self::$subscribe = $type;
                break;
        }
    }
}
