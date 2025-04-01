#!/usr/bin/env php
<?php

use Workerman\Connection\AsyncUdpConnection;
use Workerman\Worker;

require_once __DIR__ . '/../../../vendor/autoload.php';

// 创建一个UDP Worker用于接收服务器的响应
$receiver = new Worker('udp://0.0.0.0:0');
$port = 0;

$receiver->onWorkerStart = function ($worker) use (&$port) {
    // 获取系统分配的端口
    $socket_name = stream_socket_get_name($worker->getMainSocket(), false);
    $port = explode(':', $socket_name)[1];
    echo "监听端口: {$socket_name}\n";

    // 创建一个异步UDP连接发送ping
    $udp_connection = new AsyncUdpConnection('udp://127.0.0.1:7410');

    // 当连接建立时发送ping
    $udp_connection->onConnect = function ($connection) use ($port) {
        echo "UDP连接已建立\n";
        // 在ping消息中包含监听端口
        $connection->send("ping:{$port}");
        echo "已发送ping到服务器，本地端口: {$port}\n";
    };

    $udp_connection->onError = function ($connection, $code, $msg) {
        echo "连接错误: [{$code}] {$msg}\n";
    };

    $udp_connection->connect();
};

// 当收到数据时
$receiver->onMessage = function ($connection, $data) {
    echo "收到原始数据: " . $data . "\n";
    // if (strpos($data, 'timestamp_') === 0) {
    //     echo "收到时间戳消息: {$data}\n";
    // } else {
    //     echo "收到服务器响应: {$data}\n";
    // }
};

Worker::$pidFile = __DIR__ . '/udp_client.pid';
Worker::$logFile = __DIR__ . '/udp_client.log';

// 运行worker
Worker::runAll();
