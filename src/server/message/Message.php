<?php
declare(strict_types=1);

namespace whaleFallWh\SwooleMqttServer\Server\Message;


class Message
{
    private $msg;

    private static $instance;

    private function __construct()
    {
    }

    private function __clone()
    {
    }

    public static function instance(): self
    {
        if (!(self::$instance instanceof self)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function add(int $message_id, $data): void
    {
        $this->msg[$message_id] = $data;
    }

    public function del(int $message_id): void
    {
        unset($this->msg[$message_id]);
    }
}