<?php
declare(strict_types=1);

namespace whaleFallWh\SwooleMqttServer\Server\Message;

use Co\System;
use whaleFallWh\SwooleMqttServer\Config;

class MessageId
{
    private $atomic=null;

    /** @var string|null message存储路径 */
    private $filePath;

    private static $instance;

    /**
     * MessageId constructor. 多个worker造成进程隔离，使用atomic。
     *
     * @link https://wiki.swoole.com/#/memory/atomic
     */
    private function __construct()
    {
        $this->atomic = new \Swoole\Atomic();
        $this->filePath = Config::getInstance()->get('message_dir', BASE_PATH . '/runtime/message/');
        if(!is_dir($this->filePath)) {
            mkdir($this->filePath);
        }
    }

    /**
     * @return MessageId
     */
    public static function instance(): self
    {
        if (!(self::$instance instanceof self)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 自增
     *
     * @return int
     */
    public function incr(): int
    {
        $message_id = $this->atomic->add(1);
        if ($message_id >= 65535) {
            $message_id = $this->initMsgId();
        }
        return $message_id;
    }

    /**
     * 初始化messageId
     *
     * @return int
     */
    public function initMsgId(): int
    {
        $msgId = System::readFile($this->getMsgIdFilePath());
        if (!$msgId) {
            $this->atomic->set(1);
        } else {
            $this->atomic->set(unserialize($msgId));
        }
        return $this->atomic->get();
    }

    /**
     * 获取保存messageID的文件路径
     *
     * @return string
     */
    private function getMsgIdFilePath(): string
    {
        return $this->filePath . 'messageId';
    }

    public function saveMsgId()
    {
        go(function () {
            System::writeFile($this->getMsgIdFilePath(), serialize($this->atomic->get()));
        });
    }
}