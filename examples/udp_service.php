#!/usr/bin/env php
<?php

use Workerman\Connection\AsyncUdpConnection;
use Workerman\Connection\UdpConnection;
use Workerman\Timer;
use Workerman\Worker;

require_once __DIR__ . '/../../../vendor/autoload.php';

// 创建一个UDP Worker监听7410端口
$worker = new Worker('udp://0.0.0.0:7410');

// 当收到UDP数据时
$worker->onMessage = function (UdpConnection $connection, $data): void {
    $data = trim($data);

    // 解析ping消息和端口
    if (0 === strpos($data, 'ping:')) {
        $clientPort = substr($data, 5);
        echo "收到来自客户端的ping，监听端口为: {$clientPort}\n";

        // 记录客户端地址信息
        [$clientAddress] = explode(':', $connection->getRemoteAddress());
        echo "客户端地址: {$clientAddress}\n";

        // 创建一个计数器
        $count = 0;

        // 每秒发送一次pong，总共发送10次
        $timer_id = Timer::add(1, function () use ($clientAddress, $clientPort, &$count, &$timer_id): void {
            ++$count;

            // 创建一个异步UDP连接发送pong
            $pong_connection = new AsyncUdpConnection("udp://{$clientAddress}:{$clientPort}");
            $pong_connection->onConnect = function ($connection) use ($count, $clientPort): void {
                echo "发送第 {$count} 次pong到端口 {$clientPort}\n";
                $connection->send('pong');
                Timer::add(0.1, function () use ($connection): void {
                    $connection->close();
                }, [], false);
            };
            $pong_connection->connect();

            // 创建一个异步UDP连接发送时间戳
            $timestamp_connection = new AsyncUdpConnection("udp://{$clientAddress}:{$clientPort}");
            $timestamp_connection->onConnect = function ($connection) use ($count, $clientPort): void {
                $timestamp = microtime(true);
                $message = "timestamp_{$count}:{$timestamp}";
                echo "发送时间戳消息到端口 {$clientPort}: {$message}\n";
                $connection->send($message);
                Timer::add(0.1, function () use ($connection): void {
                    $connection->close();
                }, [], false);
            };
            $timestamp_connection->connect();

            // 发送10次后停止定时器
            if ($count >= 10) {
                Timer::del($timer_id);
            }
        });
    }
};

Worker::$pidFile = __DIR__ . '/udp_service.pid';
Worker::$logFile = __DIR__ . '/udp_service.log';

// 运行worker
Worker::runAll();
