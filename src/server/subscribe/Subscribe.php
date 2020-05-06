<?php


namespace whaleFallWh\SwooleMqttServer\Server\Subscribe;


use whaleFallWh\SwooleMqttServer\Config;

class Subscribe
{
    /** @var $subscribe SubscribeInterface */
    public static $subscribe;

    public static function init()
    {
        $config = Config::getInstance()->get('subscribe', []);
        $type = $config['type'] ?? '';
        switch ($type) {
            case '':
                self::$subscribe = MemSubscribeFds::class;
                break;
            case 'file':
                self::$subscribe = FileSubscribeFds::class;
                break;
            case 'redis':
                self::$subscribe = RedisSubscribeFds::class;
                break;
            default:
                self::$subscribe = $type;
                break;
        }
    }

}
