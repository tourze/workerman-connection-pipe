<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/container_helper.php';

use Tourze\Workerman\ConnectionPipe\Container;
use Tourze\Workerman\ConnectionPipe\Event\DataForwardedEvent;
use Tourze\Workerman\ConnectionPipe\Event\ForwardFailedEvent;
use Tourze\Workerman\ConnectionPipe\PipeFactory;
use Workerman\Connection\AsyncTcpConnection;
use Workerman\Timer;
use Workerman\Worker;

// 创建日志处理器
$logger = new \Monolog\Logger('udp_to_tcp');
$logger->pushHandler(new \Monolog\Handler\StreamHandler('php://stdout', \Monolog\Logger::DEBUG));
Container::getInstance()->setLogger($logger);

// 创建事件分发器
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
Container::getInstance()->setEventDispatcher($eventDispatcher);

// 创建UDP服务器
$udpServer = new Worker('udp://0.0.0.0:8000');
$udpServer->count = 4;

// 设置转发目标TCP地址
$targetAddress = 'tcp://127.0.0.1:8001';

// UDP服务器回话跟踪
$connections = [];

$udpServer->onWorkerStart = function ($worker) use (&$connections, $targetAddress) {
    // 在启动时清空连接数组
    $connections = [];
};

$udpServer->onMessage = function ($connection, $data, $clientAddress, $clientPort) use (&$connections, $targetAddress) {
    $clientKey = "{$clientAddress}:{$clientPort}";
    echo "收到UDP客户端消息: {$clientKey}\n";

    // 检查是否已经有此客户端的TCP连接
    if (!isset($connections[$clientKey])) {
        echo "为UDP客户端 {$clientKey} 创建新的TCP连接\n";

        // 创建到目标TCP服务器的连接
        $targetConnection = new AsyncTcpConnection($targetAddress);

        $targetConnection->onConnect = function ($targetConn) use ($connection, $clientAddress, $clientPort, &$connections, $clientKey) {
            echo "已连接到目标TCP服务器: {$targetConn->id} 为UDP客户端: {$clientKey}\n";

            // 创建UDP到TCP的转发管道
            $pipe = PipeFactory::createUdpToTcp($connection, $targetConn);

            // 保存到连接映射
            $connections[$clientKey] = [
                'target' => $targetConn,
                'pipe' => $pipe,
                'last_active' => time(),
                'address' => $clientAddress,
                'port' => $clientPort
            ];
        };

        $targetConnection->onMessage = function ($targetConn, $data) use ($connection, $clientAddress, $clientPort, $clientKey, &$connections) {
            echo "从TCP服务器收到回复: " . substr($data, 0, 20) . (strlen($data) > 20 ? "..." : "") . " 发送到UDP客户端: {$clientKey}\n";

            // 查找对应的UDP客户端地址
            if (isset($connections[$clientKey]) && $connections[$clientKey]['pipe']) {
                $pipe = $connections[$clientKey]['pipe'];
                // 使用管道的sendToUdp方法发送数据回UDP客户端
                $pipe->sendToUdp($data, $clientAddress, $clientPort);
                // 更新最后活动时间
                $connections[$clientKey]['last_active'] = time();
            }
        };

        $targetConnection->onClose = function ($targetConn) use ($clientKey, &$connections) {
            echo "目标TCP连接已关闭: {$targetConn->id} 对应UDP客户端: {$clientKey}\n";
            if (isset($connections[$clientKey])) {
                if (isset($connections[$clientKey]['pipe'])) {
                    $connections[$clientKey]['pipe']->close();
                }
                unset($connections[$clientKey]);
            }
        };

        $targetConnection->onError = function ($targetConn, $code, $msg) use ($clientKey, &$connections) {
            echo "目标TCP连接错误: $code $msg 对应UDP客户端: {$clientKey}\n";
            if (isset($connections[$clientKey])) {
                if (isset($connections[$clientKey]['pipe'])) {
                    $connections[$clientKey]['pipe']->close();
                }
                unset($connections[$clientKey]);
            }
        };

        $targetConnection->connect();
    } else {
        // 更新现有连接的最后活动时间
        $connections[$clientKey]['last_active'] = time();

        // 使用现有管道转发数据
        $pipe = $connections[$clientKey]['pipe'];
        $pipe->forward($data, $clientAddress, $clientPort);
    }
};

// 定期清理不活跃的连接
$udpServer->onWorkerStart = function ($worker) use (&$connections) {
    Timer::add(60, function () use (&$connections) {
        $now = time();
        $timeout = 300; // 5分钟超时

        foreach ($connections as $clientKey => $info) {
            if (($now - $info['last_active']) > $timeout) {
                echo "清理不活跃连接: {$clientKey}\n";
                if (isset($info['pipe'])) {
                    $info['pipe']->close();
                }
                if (isset($info['target'])) {
                    $info['target']->close();
                }
                unset($connections[$clientKey]);
            }
        }

        echo "当前活跃UDP客户端数: " . count($connections) . "\n";
    });
};

// 创建目标TCP服务器（用于测试）
$targetServer = new Worker('tcp://0.0.0.0:8001');
$targetServer->onMessage = function ($connection, $data) {
    echo "目标TCP服务器收到数据: " . substr($data, 0, 20) . (strlen($data) > 20 ? "..." : "") . PHP_EOL;

    // 回复客户端
    $connection->send("TCP服务器已收到 " . strlen($data) . " 字节数据");
};

// 运行所有Worker
Worker::runAll();
