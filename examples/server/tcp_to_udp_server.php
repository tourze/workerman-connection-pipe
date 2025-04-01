<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/container_helper.php';

use Tourze\Workerman\ConnectionPipe\Container;
use Tourze\Workerman\ConnectionPipe\Event\DataForwardedEvent;
use Tourze\Workerman\ConnectionPipe\Event\ForwardFailedEvent;
use Tourze\Workerman\ConnectionPipe\PipeFactory;
use Workerman\Connection\AsyncUdpConnection;
use Workerman\Worker;

// 设置日志处理器
$logger = new \Monolog\Logger('tcp_to_udp');
$logger->pushHandler(new \Monolog\Handler\StreamHandler('php://stdout', \Monolog\Logger::DEBUG));
Container::setLogger($logger);

// 设置事件分发器
$eventDispatcher = new \Symfony\Component\EventDispatcher\EventDispatcher();
$eventDispatcher->addListener(DataForwardedEvent::class, function (DataForwardedEvent $event) {
    $metadata = $event->getMetadata();
    echo "数据转发成功: {$event->getSourceProtocol()}->{$event->getTargetProtocol()} " .
        "长度: " . strlen($event->getData()) .
        (isset($metadata['direction']) ? " 方向: {$metadata['direction']}" : "") . PHP_EOL;
});
$eventDispatcher->addListener(ForwardFailedEvent::class, function (ForwardFailedEvent $event) {
    echo "数据转发失败: {$event->getSourceProtocol()}->{$event->getTargetProtocol()} " .
        "原因: {$event->getReason()}" . PHP_EOL;
});
Container::setEventDispatcher($eventDispatcher);

// 创建TCP服务器
$tcpServer = new Worker('tcp://0.0.0.0:8000');
$tcpServer->count = 4;

// 设置转发目标UDP地址
$targetAddress = 'udp://127.0.0.1:8001';

$tcpServer->onConnect = function ($connection) use ($targetAddress) {
    echo "新客户端连接: {$connection->id}\n";

    // 创建到目标UDP服务器的连接
    $targetConnection = new AsyncUdpConnection($targetAddress);

    $targetConnection->onConnect = function ($targetConn) use ($connection) {
        echo "已连接到目标UDP服务器\n";

        // 创建TCP到UDP的转发管道
        $pipe = PipeFactory::createTcpToUdp($connection, $targetConn);

        // 保存pipe到连接对象，防止被GC回收
        $connection->pipe = $pipe;
        $targetConn->pipe = $pipe;
    };

    $targetConnection->onMessage = function ($targetConn, $data) use ($connection) {
        echo "从UDP服务器收到回复: " . substr($data, 0, 20) . (strlen($data) > 20 ? "..." : "") . PHP_EOL;

        // 直接将数据返回给TCP客户端
        $connection->send($data);
    };

    $targetConnection->onClose = function () use ($connection) {
        echo "目标UDP服务器连接已关闭\n";
    };

    $targetConnection->onError = function ($targetConn, $code, $msg) use ($connection) {
        echo "目标UDP服务器连接错误: $code $msg\n";
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

// 创建目标UDP服务器（用于测试）
$targetServer = new Worker('udp://0.0.0.0:8001');
$targetServer->onMessage = function ($connection, $data) {
    echo "目标UDP服务器收到数据: " . substr($data, 0, 20) . (strlen($data) > 20 ? "..." : "") . PHP_EOL;

    // 回复客户端
    $connection->send("UDP服务器已收到 " . strlen($data) . " 字节数据");
};

// 运行所有Worker
Worker::runAll();
