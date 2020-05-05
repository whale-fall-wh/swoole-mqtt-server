<?php


namespace whaleFallWh\SwooleMqttServer\Server\Subscribe;


use Co\System;
use whaleFallWh\SwooleMqttServer\Config;

class FileSubscribeFds implements SubscribeInterface
{
    private static $instance;

    private $filePath;

    private function __construct()
    {
        $config = Config::getInstance()->get('subscribe', []);
        $this->filePath = $config['path'] ?? BASE_PATH . '/runtime/subscribe/';
        if(!is_dir($this->filePath)) {
            mkdir($this->filePath);
        }
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
        go(function () use ($topic, $fd) {
            $fdFile = $this->getTopicDir($topic) . $fd;
            System::writeFile($fdFile, $fd);
        });
    }

    public function unSub(string $topic, int $fd)
    {
        go(function () use ($topic, $fd) {
            unlink($this->getTopicDir($topic) . $fd);
        });
    }

    public function getSubscribeFbs($path=''): array
    {
        $subscribeFds=[];
        $p = $path ? scandir($this->filePath. $path . '/') : scandir($this->filePath);
        foreach ($p as $val) {
            if($val == '.' || $val == '..') {
                continue;
            }
            if(is_dir($this->filePath.$val)){
                $subscribeFds[$val] = $this->getSubscribeFbs($val);
            } else {
                $subscribeFds[] = $val;
            }
        }
        return $subscribeFds;
    }

    public function getSubscribeFbsByTopic(string $topic): array
    {
        $fds = [];
        $subscribeFds = $this->getSubscribeFbs();
//        var_dump($subscribeFds);
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

    private function getTopicDir(string $topic): string
    {
        $topic = str_replace('/', '@', $topic);
        $path = $this->filePath . $topic . '/';
        if (!is_dir($path)) {
            mkdir($path);
        }
        return $path;
    }

    public function clearFds()
    {
        $p = $this->filePath;
        deldir($p);
    }
}