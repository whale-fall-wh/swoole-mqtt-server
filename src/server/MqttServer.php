<?php
namespace whaleFallWh\SwooleMqttServer\Server;

use Swoole\Server;
use Exception;
use whaleFallWh\SwooleMqttServer\Packet;

class MqttServer {

    /** @var Server */
    private $server;
    private $clients = []; // [fd]=>Client
    private $subscribeFds = []; // [topic]=>[fd]=>fd

    const COMMAND_CONNECT = 1;
    const COMMAND_CONNACK = 2;
    const COMMAND_PUBLISH = 3;
    const COMMAND_PUBACK = 4;
    const COMMAND_PUBREC = 5;
    const COMMAND_PUBREL = 6;
    const COMMAND_PUBCOMP = 7;
    const COMMAND_SUBSCRIBE = 8;
    const COMMAND_SUBACK = 9;
    const COMMAND_UNSUBSCRIBE = 10;
    const COMMAND_UNSUBACK = 11;
    const COMMAND_PINGREQ = 12;
    const COMMAND_PINGRESP = 13;
    const COMMAND_DISCONNECT = 14;

    const QOS0 = 0;
    const QOS1 = 1;
    const QOS2 = 2;

    public function __construct($host='0.0.0.0', $port=9501)
    {
        $this->server = new Server($host, $port);
    }

    public function start($config)
    {
        $this->server->set($config);
        $this->server->on('connect', [$this, 'onConnect']);
        $this->server->on('receive', [$this, 'onReceive']);
        $this->server->on('close', [$this, 'onClose']);
        $this->server->start();
    }

    /**
     * @param $fd
     * @param $data
     *
     * @throws Exception
     */
    private function handleCommand($fd, $data)
    {
        $packet = new Packet($data);
        $header = $packet->getFixedHeader();
        switch ($header['type']) {
            case self::COMMAND_CONNECT:
                $connectInfo = $packet->getConnectInfo();
                $clientId = $connectInfo['clientId'];
                echo "clientId($clientId) connect" . PHP_EOL;
                $resp = $this->makeCONNACKData();
                $this->server->send($fd, $resp);
                $client = new Client($fd, $connectInfo);
                $this->clients["$fd"] = $client;
                break;
            case self::COMMAND_PUBLISH:
                $topic = $packet->getString();
                $msg = $packet->getRemain();
                echo "topic($topic) - " . "msg($msg)" . PHP_EOL;
                $this->publish($topic, $data);
                break;
            case self::COMMAND_SUBSCRIBE:
                if ($header['reserved'] != 0x2) {
                    throw new Exception('reserved is no 2\n');
                }
                $packetIdentifier = $packet->getMSBAndLSBValue();
                $topic = $packet->getString();
                $qos = $packet->getByte();
                $this->subscribe($topic, $fd);
                $resp = $this->makeSUBACKData($packetIdentifier, $qos);
                $this->server->send($fd, $resp);
                break;
            case self::COMMAND_UNSUBSCRIBE:
                if ($header['reserved'] != 0x2) {
                    throw new Exception('reserved is no 2\n');
                }
                $packetIdentifier = $packet->getMSBAndLSBValue();
                $topic = $packet->getString();
                $this->unsubscribe($topic, $fd);
                $resp = $this->makeUNSUBACKData($packetIdentifier);
                $this->server->send($fd, $resp);
                break;
            case self::COMMAND_PINGREQ:
                $resp = $this->makePINGREQData();
                $this->server->send($fd, $resp);
                break;
            case self::COMMAND_DISCONNECT:
                break;
            default:
                throw new Exception('illegal MQTT Control Packet command\n');
                break;
        }
    }

    private function publish($topic, $data)
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
        if (!$fds) {
            return;
        }
        foreach ($fds as $fd) {
            go(function () use ($fd, $data){
                 $this->server->send($fd, $data);
            });
        }
    }

    private function subscribe($topic, $fd)
    {
        $this->subscribeFds[$topic][$fd] = $fd;
    }

    private function unsubscribe($topic, $fd)
    {
        unset($this->subscribeFds[$topic][$fd]);
    }

    private function makeCONNACKData()
    {
        return chr(32) . chr(2) . chr(0) . chr(0);
    }

    private function makePINGREQData()
    {
        return chr(208) . chr(0);
    }

    private function makeSUBACKData($packageIdentifier, $qos)
    {
        $data = chr(0x90);
        $length = 3; // 暂时只支持一个主题,报文标识符MSB+LSB+topic
        $data .= chr($length);
        $byte = intval($packageIdentifier/256);
        $data .= chr($byte);
        $byte = intval($packageIdentifier%256);
        $data .= chr($byte);
        $data .= chr($qos);

        return $data;
    }

    private function makeUNSUBACKData($packageIdentifier)
    {
        $data = chr(0xB0);
        $data .= chr(2);
        $byte = intval($packageIdentifier/256);
        $data .= chr($byte);
        $byte = intval($packageIdentifier%256);
        $data .= chr($byte);
        return $data;
    }

    public function onConnect(Server $server, $fd)
    {
        echo "connection open: {$fd}" . PHP_EOL;
    }

    public function onReceive(Server $server, $fd, $reactor_id, $data)
    {
        try {
            $this->handleCommand($fd, $data);
        } catch (Exception $e) {
            echo $e->getMessage();
            $this->server->close($fd);
        }
    }

    public function onClose(Server $server, $fd)
    {
        echo "connection close: {$fd}" . PHP_EOL;
        unset($this->clients["$fd"]);
    }

}




