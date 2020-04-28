<?php
declare(strict_types=1);

namespace whaleFallWh\SwooleMqttServer\Server;


class Message
{
    public $topic;
    public $content;

    public function __construct(string $topic, string $content)
    {
        $this->topic = $topic;
        $this->content = $content;
    }
}