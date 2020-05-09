<?php


namespace whaleFallWh\SwooleMqttServer\Server\Subscribe;


use whaleFallWh\SwooleMqttServer\Libs\Redis\RedisPool;

class RedisSubscribeFds implements SubscribeInterface
{
    private static $instance;

    private $subPrefix;

    private function __construct()
    {
        $this->subPrefix = 'sub_topic:';
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
        $redis = RedisPool::instance()->getConnection();
        $redis->sAdd($this->getKeyByTopic($topic), $fd);
    }

    public function unSub(string $topic, int $fd)
    {
        $redis = RedisPool::instance()->getConnection();
        $redis->sRem($this->getKeyByTopic($topic), $fd);
    }

    public function getSubscribeFds(): array
    {
        $fds = [];
        $redis = RedisPool::instance()->getConnection();
        $allTopics = $redis->keys($this->subPrefix . '*');
        foreach ($allTopics as $key) {
            $topic = str_replace($this->subPrefix, '', $key);
            $fds[$topic] = $redis->sMembers($key);
        }
        return $fds;
    }

    public function getSubscribeFdsByTopic(string $topic): array
    {
        $subscribeFds = $this->getSubscribeFds();
        $fds = [];
        $topics = array_keys($subscribeFds);
        foreach ($topics as $item) {
            $pattern = str_replace(array('/','+','#'),array('\\/','[^\\/]+','(.+)'),$item);
            $pattern = '/^' . $pattern . '$/';
            $rs = preg_match($pattern,$topic);
            if ($rs) {
                $fds = array_merge($fds, $subscribeFds[$item]);
            }
        }
        $fds = array_unique($fds);
        return $fds;
    }

    public function clearFds()
    {
        \Co\run(function () {
            $redis = RedisPool::instance()->getConnection();
            $allTopics = $redis->keys($this->subPrefix . '*');
            foreach ($allTopics as $key) {
                $redis->del($key);
            }
        });
    }

    public function clearFdsByfd(int $fd)
    {
        $redis = RedisPool::instance()->getConnection();
        $allTopics = $redis->keys($this->subPrefix . '*');
        foreach ($allTopics as $key) {
            if ($redis->sIsMember($key, $fd)) {
                $redis->sRem($key, $fd);
            }
        }
    }

    private function getKeyByTopic(string $topic): string
    {
        return $this->subPrefix . $topic;
    }
}
