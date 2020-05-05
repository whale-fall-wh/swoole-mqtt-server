<?php
declare(strict_types=1);

namespace whaleFallWh\SwooleMqttServer\Server\Subscribe;


interface SubscribeInterface
{

    public static function instance() ;
    /**
     * 订阅
     * @param string $topic
     * @param int $fd
     * @return mixed
     */
    public function sub(string $topic, int $fd);

    /**
     * 取消订阅
     * @param string $topic
     * @param int $fd
     * @return mixed
     */
    public function unSub(string $topic, int $fd);

    /**
     * 获取所有的订阅关系
     * @return array
     */
    public function getSubscribeFbs(): array;

    /**
     * 通过主题获取所有的fd
     * @param string $topic
     * @return array
     */
    public function getSubscribeFbsByTopic(string $topic): array;

    public function clearFds();
}