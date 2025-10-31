<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/container_helper.php';

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Tourze\Workerman\ConnectionPipe\Container;
use Tourze\Workerman\ConnectionPipe\Event\DataForwardedEvent;
use Tourze\Workerman\ConnectionPipe\Event\ForwardFailedEvent;
use Tourze\Workerman\ConnectionPipe\PipeFactory;
use Workerman\Connection\AsyncUdpConnection;
use Workerman\Timer;
use Workerman\Worker;

// 创建日志处理器
$logger = new Logger('udp_to_udp');
$logger->pushHandler(new StreamHandler('php://stdout', Logger::DEBUG));
Container::getInstance()->setLogger($logger);

// 创建事件分发器
$eventDispatcher = new EventDispatcher();
$eventDispatcher->addListener(DataForwardedEvent::class, function (DataForwardedEvent $event): void {
    $metadata = $event->getMetadata();
    echo "数据转发成功: {$event->getSourceProtocol()}->{$event->getTargetProtocol()} " .
        '长度: ' . strlen($event->getData()) .
        (isset($metadata['direction']) ? " 方向: {$metadata['direction']}" : '') . PHP_EOL;
});
$eventDispatcher->addListener(ForwardFailedEvent::class, function (ForwardFailedEvent $event): void {
    echo "数据转发失败: {$event->getSourceProtocol()}->{$event->getTargetProtocol()} " .
        "原因: {$event->getReason()}" . PHP_EOL;
});
Container::getInstance()->setEventDispatcher($eventDispatcher);

// 创建UDP服务器
$udpServer = new Worker('udp://0.0.0.0:8000');
$udpServer->count = 4;

// 设置转发目标UDP地址
$targetAddress = 'udp://127.0.0.1:8001';

// 是否启用Fullcone NAT
$enableFullconeNat = true;
$natTimeout = 300; // 5分钟超时

// UDP服务器回话跟踪
$connections = [];

$udpServer->onWorkerStart = function ($worker) use (&$connections, $targetAddress): void {
    // 在启动时清空连接数组
    $connections = [];

    // 创建到目标UDP服务器的共享连接
    $targetConnection = new AsyncUdpConnection($targetAddress);

    $targetConnection->onConnect = function ($targetConn) use ($worker, &$connections): void {
        echo "已连接到目标UDP服务器\n";
        // 存储共享的目标连接
        $worker->targetConnection = $targetConn;
    };

    $targetConnection->onMessage = function ($targetConn, $data, $targetAddress, $targetPort) use (&$connections): void {
        echo '从目标UDP服务器收到回复: ' . substr($data, 0, 20) . (strlen($data) > 20 ? '...' : '') . PHP_EOL;

        // 查找对应的源客户端（如果使用FullconeNAT，管道会自动处理回复路由）
    };

    $targetConnection->onClose = function () use ($worker): void {
        echo "目标UDP服务器连接已关闭\n";
        // 尝试重新连接
        Timer::add(5, function () use ($worker): void {
            if (isset($worker->targetConnection)) {
                $worker->targetConnection->connect();
            }
        }, [], false);
    };

    $targetConnection->onError = function ($targetConn, $code, $msg): void {
        echo "目标UDP服务器连接错误: {$code} {$msg}\n";
    };

    $targetConnection->connect();

    // 定期清理不活跃的连接
    Timer::add(60, function () use (&$connections): void {
        $now = time();
        $timeout = 300; // 5分钟超时

        $totalMappings = 0;
        $clearedPipes = 0;

        foreach ($connections as $clientKey => $info) {
            if (($now - $info['last_active']) > $timeout) {
                echo "清理不活跃连接: {$clientKey}\n";
                if (isset($info['pipe'])) {
                    // 获取NAT映射数量
                    $mappings = $info['pipe']->getAllNatMappings();
                    $totalMappings += count($mappings);

                    // 关闭管道
                    $info['pipe']->close();
                    ++$clearedPipes;
                }
                unset($connections[$clientKey]);
            }
        }

        echo '当前活跃UDP客户端数: ' . count($connections) . ", 已清理: {$clearedPipes}, 总NAT映射: {$totalMappings}\n";
    });
};

$udpServer->onMessage = function ($connection, $data, $clientAddress, $clientPort) use (&$connections): void {
    $clientKey = "{$clientAddress}:{$clientPort}";
    echo "收到UDP客户端消息: {$clientKey}\n";

    // 获取Worker实例和目标连接
    $worker = $connection->worker;
    $targetConnection = $worker->targetConnection;

    if (!$targetConnection) {
        echo "目标UDP服务器连接不可用\n";

        return;
    }

    // 检查是否已经有此客户端的管道
    if (!isset($connections[$clientKey])) {
        echo "为UDP客户端 {$clientKey} 创建新的转发管道\n";

        // 创建UDP到UDP的转发管道
        $pipe = PipeFactory::createUdpToUdp($connection, $targetConnection);

        // 保存到连接映射
        $connections[$clientKey] = [
            'pipe' => $pipe,
            'last_active' => time(),
            'address' => $clientAddress,
            'port' => $clientPort,
        ];
    } else {
        // 更新现有连接的最后活动时间
        $connections[$clientKey]['last_active'] = time();
    }

    // 使用管道转发数据
    $pipe = $connections[$clientKey]['pipe'];
    $pipe->forward($data, $clientAddress, $clientPort);
};

// 创建目标UDP服务器（用于测试）
$targetServer = new Worker('udp://0.0.0.0:8001');
$targetServer->onMessage = function ($connection, $data, $clientAddress, $clientPort): void {
    echo '目标UDP服务器收到数据: ' . substr($data, 0, 20) . (strlen($data) > 20 ? '...' : '') .
        " 来自: {$clientAddress}:{$clientPort}" . PHP_EOL;

    // 回复客户端
    $connection->send('UDP服务器已收到 ' . strlen($data) . ' 字节数据');
};

// 运行所有Worker
Worker::runAll();
