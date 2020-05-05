<?php


namespace whaleFallWh\SwooleMqttServer\Server\Subscribe;


class RedisSubscribeFds implements SubscribeInterface
{
    private static $instance;

    private function __construct()
    {
    }

    private function __clone()
    {
    }

    public static function instance()
    {
        if (!(self::$instance instanceof self)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function sub(string $topic, int $fd)
    {

    }

    public function unSub(string $topic, int $fd)
    {

    }

    public function getSubscribeFbs(): array
    {

    }

    public function getSubscribeFbsByTopic(string $topic): array
    {

    }

    public function clearFds()
    {

    }
}