<?php
declare(strict_types=1);


namespace whaleFallWh\SwooleMqttServer;

class SubscribeFds
{
    private $subscribeFds = [];

    private static $instance;

    private function __construct()
    {
    }

    private function __clone()
    {
    }

    public static function instance()
    {
        if (! (self::$instance instanceof self)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function sub(string $topic, int $fd)
    {
        $this->subscribeFds[$topic][$fd] = $fd;
    }

    public function unSub(string $topic, int $fd)
    {
        unset($this->subscribeFds[$topic][$fd]);
    }

    public function getSubcribeFbs()
    {
        return $this->subscribeFds;
    }

    public function getSubsribeFbsByTopic($topic)
    {
        $fds = [];
        $topics = array_keys($this->subscribeFds);
        foreach ($topics as $item) {
            $pattern = str_replace(array('/','+','#'),array('\\/','[^\\/]+','(.+)'),$item);
            $pattern = '/^' . $pattern . '$/';
            $rs = preg_match($pattern,$topic);
            if ($rs) {
                $fds = array_merge($fds, $this->subscribeFds[$item]);
            }
        }
        $fds = array_unique($fds);
        return $fds;
    }

}
