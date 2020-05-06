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
        $redis = RedisPool::instance()->get();
        $redis->sAdd($this->getKeyByTopic($topic), $fd);
        RedisPool::instance()->put($redis);
    }

    public function unSub(string $topic, int $fd)
    {
        $redis = RedisPool::instance()->get();
        $redis->sRem($this->getKeyByTopic($topic), $fd);
        RedisPool::instance()->put($redis);
    }

    public function getSubscribeFds(): array
    {
        $fds = [];
        $redis = RedisPool::instance()->get();
        $allTopics = $redis->keys($this->subPrefix . '*');
        foreach ($allTopics as $key) {
            $topic = str_replace($this->subPrefix, '', $key);
            $fds[$topic] = $redis->sMembers($key);
        }
        RedisPool::instance()->put($redis);
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
            $redis = RedisPool::instance()->get();
            $allTopics = $redis->keys($this->subPrefix . '*');
            foreach ($allTopics as $key) {
                $redis->del($key);
            }
            RedisPool::instance()->put($redis);
        });
    }

    private function getKeyByTopic(string $topic): string
    {
        return $this->subPrefix . $topic;
    }
}