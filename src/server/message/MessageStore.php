<?php
declare(strict_types=1);

namespace whaleFallWh\SwooleMqttServer\Server\Message;


class MessageStore
{
    private $store=[];

    private $messageById=[];

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

    /**
     * 转发给每个订阅者都需要存储一份msg
     *
     * @param int $message_id
     * @param int $id 发布者的 message_id, 可以由此获取message信息
     */
    public function addMsgToStore(int $message_id, array $packet)
    {
        $id = $packet['message_id'];
        $this->store[$message_id] = $id;
        $this->messageById[$id][$message_id] = $packet;
        var_dump($this->store);
        var_dump($this->messageById);
    }

    public function delMsgFromStore(int $message_id)
    {
        $id = $this->store[$message_id];
        unset($this->store[$message_id]);
        unset($this->messageById[$id][$message_id]);
        if (empty($this->messageById[$id])) { // TODO 多个worker可能会异常，待测试
            unset($this->messageById[$id]);
        }
        var_dump($this->store);
        var_dump($this->messageById);
    }

}
