<?php
/**
 * Server.php
 * Created on 2019/7/29 16:19
 * Created by Wilin
 */

class Server {
    private $server;
    private $table;

    public function __construct() {

        $this->server = new swoole_websocket_server("0.0.0.0",9501);
        $this->server->on('open', array($this,'onOpen'));
        $this->server->on('message', array($this,'onMessage'));
        $this->server->on('close', array($this,'onClose'));

        $this->createTable();
        $this->server->start();
    }

    public function onOpen(\Swoole\WebSocket\Server $server, $request) {
        echo $request->fd . '连接了' . PHP_EOL;
        $user = [
            'fd' => $request->fd,
            'name' => '用户' . $request->fd,
        ];

        $this->table->set($request->fd, $user);
    }

    public function onMessage(\Swoole\WebSocket\Server $server, $frame) {
        $name = $this->table->get($frame->fd)['name'];
        echo $name . '发来消息：' . $frame->data . PHP_EOL;
        foreach ($this->table as $user) {
            if ($user['fd'] == $frame->fd) {
                continue;
            }
            $server->push($user['fd'], json_encode(['username' => $name, 'message' => $frame->data ]));
        }
    }

    public function onClose($server, $fd) {
        $name = $this->table->get($fd)['name'];
        echo $name . '断开连接，内存表删除：' . $this->table->del($fd) . PHP_EOL;
    }

    private function createTable() {
        $this->table = new \Swoole\Table(1024);
        $this->table->column('fd', \Swoole\Table::TYPE_INT);
        $this->table->column('name', \Swoole\Table::TYPE_STRING);
        $this->table->create();
    }
}

$server = new Server();