<?php
declare(strict_types=1);

namespace whaleFallWh\SwooleMqttServer\Server\Message;


use Co\System;
use whaleFallWh\SwooleMqttServer\Config;

class MessageStore
{
    private $store=[];

    /** @var string|null message存储路径 */
    private $filePath;

    private static $instance;

    private function __construct()
    {
        $this->filePath = Config::getInstance()->get('message_dir', BASE_PATH . '/runtime/message/');
        if(!is_dir($this->filePath)) {
            mkdir($this->filePath);
        }
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

    private function getMsgPath(int $message_id)
    {
        return $this->filePath . $message_id . '.txt';
    }

    /**
     * 转发给每个订阅者都需要存储一份msg
     *
     * @param int $message_id
     * @param array $packet
     */
    public function addMsgToStore(int $messageId, array $packet): void
    {
        go(function () use ($messageId, $packet) {
            System::writeFile($this->getMsgPath($messageId), serialize($packet));
            $this->getMsgByMsgId($messageId);
        });
    }

    public function delMsgFromStore(int $messageId): void
    {
        go(function () use ($messageId) {
            unlink($this->getMsgPath($messageId));
        });
    }

    public function getMsgByMsgId(int $messageId): array
    {
        $msg = System::readFile($this->getMsgPath($messageId));
        if (!$msg) {
            return false;
        }
        return unserialize($msg);
    }
}
