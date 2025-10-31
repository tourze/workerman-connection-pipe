<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use Workerman\Connection\AsyncTcpConnection;
use Workerman\Timer;
use Workerman\Worker;

// 要连接的TCP服务器地址
$serverAddress = 'tcp://127.0.0.1:8000';

// 创建一个Worker实例
$worker = new Worker();

// 当Worker启动时创建连接
$worker->onWorkerStart = function ($worker) use ($serverAddress): void {
    // 创建异步TCP连接
    $connection = new AsyncTcpConnection($serverAddress);

    // 设置连接成功时的回调
    $connection->onConnect = function ($connection): void {
        echo "TCP服务器连接成功\n";

        // 发送测试数据
        $testData = 'Hello, this is a TCP client test message. ' . date('Y-m-d H:i:s');
        $connection->send($testData);

        // 定期发送心跳消息
        Timer::add(5, function () use ($connection): void {
            $heartbeat = 'PING ' . date('Y-m-d H:i:s');
            echo "发送心跳: {$heartbeat}\n";
            $connection->send($heartbeat);
        });
    };

    // 设置收到服务器数据时的回调
    $connection->onMessage = function ($connection, $data): void {
        echo "收到服务器回复: {$data}\n";
    };

    // 设置连接关闭时的回调
    $connection->onClose = function ($connection): void {
        echo "TCP连接关闭，尝试重连...\n";
        // 1秒后重连
        Timer::add(1, function () use ($connection): void {
            $connection->reconnect();
        }, null, false);
    };

    // 设置连接发生错误时的回调
    $connection->onError = function ($connection, $code, $msg): void {
        echo "TCP连接错误: {$code} {$msg}\n";
    };

    // 连接服务器
    $connection->connect();
};

// 运行Worker
Worker::runAll();
