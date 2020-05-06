<?php

namespace whaleFallWh\SwooleMqttServer\Libs\Redis;

use mysql_xdevapi\Exception;
use Redis;
use Swoole\Database\RedisConfig;
use whaleFallWh\SwooleMqttServer\Config;

/**
 * @method Redis get()
 * @method void put(Redis $connection)
 */
class RedisPool extends \Swoole\ConnectionPool
{
    /** @var RedisConfig */
    protected $config;

    private static $instance;

    private function __construct(RedisConfig $config, int $size = self::DEFAULT_SIZE)
    {
        $this->config = $config;
        parent::__construct(function () {
            $redis = new Redis();
            $redis->connect(
                $this->config->getHost(),
                $this->config->getPort(),
                $this->config->getTimeout(),
                $this->config->getReserved(),
                $this->config->getRetryInterval(),
                $this->config->getReadTimeout()
            );
            if ($this->config->getAuth()) {
                $redis->auth($this->config->getAuth());
            }
            if ($this->config->getDbIndex() !== 0) {
                $redis->select($this->config->getDbIndex());
            }
            return $redis;
        }, $size);
    }

    public static function instance()
    {
        if (!self::$instance instanceof self) {
            $redisConfig = new RedisConfig;
            $config = Config::getInstance()->get('subscribe');
            if (empty($config) || empty($config['host'])) {
                throw new \Exception('subscribe redis 配置为空');
            }
            self::$instance = new self(
                $redisConfig->withHost($config['host'])
                    ->withPort($config['port'] ?? 6379)
                    ->withAuth($config['auth'] ?? '')
                    ->withDbIndex($config['dbIndex'] ?? 0)
                    ->withTimeout($config['timeout'] ?? 0),
                $size = self::DEFAULT_SIZE
            );
        }
        return self::$instance;
    }
}
