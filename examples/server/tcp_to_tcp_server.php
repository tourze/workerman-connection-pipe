<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/container_helper.php';

use Tourze\Workerman\ConnectionPipe\Container;
use Tourze\Workerman\ConnectionPipe\Event\DataForwardedEvent;
use Tourze\Workerman\ConnectionPipe\Event\ForwardFailedEvent;
use Tourze\Workerman\ConnectionPipe\PipeFactory;
use Workerman\Connection\AsyncTcpConnection;
use Workerman\Worker;

// 设置日志
$logger = new \Monolog\Logger('tcp_to_tcp');
$logger->pushHandler(new \Monolog\Handler\StreamHandler('php://stdout', \Monolog\Logger::DEBUG));
Container::getInstance()->setLogger($logger);

// 设置事件分发器
$eventDispatcher = new \Symfony\Component\EventDispatcher\EventDispatcher();
$eventDispatcher->addListener(DataForwardedEvent::class, function (DataForwardedEvent $event) {
    echo "数据转发成功: " . $event->getPipe()->getId() . " 长度: " . strlen($event->getData()) . PHP_EOL;
});
$eventDispatcher->addListener(ForwardFailedEvent::class, function (ForwardFailedEvent $event) {
    echo "数据转发失败: " . $event->getPipe()->getId() . " 原因: " . $event->getReason() . PHP_EOL;
});
Container::getInstance()->setEventDispatcher($eventDispatcher);

// 创建TCP服务器
$tcpServer = new Worker('tcp://0.0.0.0:8000');
$tcpServer->count = 4;

// 设置转发目标地址
$targetAddress = 'tcp://127.0.0.1:8001';

$tcpServer->onConnect = function ($connection) use ($targetAddress) {
    echo "新客户端连接: {$connection->id}\n";

    // 创建到目标服务器的连接
    $targetConnection = new AsyncTcpConnection($targetAddress);
    $targetConnection->onConnect = function ($targetConn) use ($connection) {
        echo "已连接到目标服务器: {$targetConn->id}\n";

        // 创建TCP到TCP的转发管道
        $pipe = PipeFactory::createTcpToTcp($connection, $targetConn);

        // 保存pipe到连接对象，防止被GC回收
        $connection->pipe = $pipe;
        $targetConn->pipe = $pipe;
    };

    $targetConnection->onClose = function () use ($connection) {
        echo "目标服务器连接已关闭\n";
        // 关闭客户端连接
        $connection->close();
    };

    $targetConnection->onError = function ($targetConn, $code, $msg) use ($connection) {
        echo "目标服务器连接错误: $code $msg\n";
        $connection->close();
    };

    $targetConnection->connect();
};

$tcpServer->onClose = function ($connection) {
    echo "客户端连接关闭: {$connection->id}\n";

    // 如果有管道，关闭管道
    if (isset($connection->pipe)) {
        $connection->pipe->close();
    }
};

// 创建目标TCP服务器（用于测试）
$targetServer = new Worker('tcp://0.0.0.0:8001');
$targetServer->onMessage = function ($connection, $data) {
    echo "目标服务器收到数据: " . substr($data, 0, 20) . (strlen($data) > 20 ? "..." : "") . PHP_EOL;
    // 回复客户端
    $connection->send("目标服务器已收到 " . strlen($data) . " 字节数据");
};

// 运行所有Worker
Worker::runAll();
