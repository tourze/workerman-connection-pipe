# Workerman Connection Pipe

[English](README.md) | [中文](README.zh-CN.md)

[![Latest Version](https://img.shields.io/packagist/v/tourze/workerman-connection-pipe.svg?style=flat-square)](https://packagist.org/packages/tourze/workerman-connection-pipe)
[![Total Downloads](https://img.shields.io/packagist/dt/tourze/workerman-connection-pipe.svg?style=flat-square)](https://packagist.org/packages/tourze/workerman-connection-pipe)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![PHP Version](https://img.shields.io/badge/php-%5E8.2-blue.svg)](https://php.net/)
[![Coverage Status](https://img.shields.io/badge/coverage-100%25-brightgreen.svg)](https://github.com/tourze/workerman-connection-pipe)

一个高性能、高可定制的连接转发框架，基于 Workerman，支持 TCP 和 UDP 连接之间的数据传输和协议转换。

## 目录

- [功能特性](#功能特性)
- [安装](#安装)
- [快速开始](#快速开始)
  - [配置](#配置)
  - [TCP 到 TCP 代理示例](#tcp-到-tcp-代理示例)
  - [UDP 到 UDP 转发示例](#udp-到-udp-转发示例)
- [监听事件](#监听事件)
- [自定义数据处理](#自定义数据处理)
- [详细工作流程](#详细工作流程)
- [高级配置](#高级配置)
  - [设置缓冲区大小](#设置缓冲区大小)
  - [启用加密传输](#启用加密传输)
- [性能优化建议](#性能优化建议)
- [问题排查](#问题排查)
- [贡献](#贡献)
- [更新日志](#更新日志)
- [许可证](#许可证)

## 功能特性

- **多协议支持**
  - TCP → TCP：支持透明 TCP 代理、负载均衡和反向代理
  - TCP → UDP：支持从 TCP 客户端访问 UDP 服务，如 DNS 代理
  - UDP → TCP：支持从 UDP 客户端访问 TCP 服务，如游戏服务器前端
  - UDP → UDP：支持 UDP 中继、流媒体转发，包含完全锥型 NAT 支持

- **高级连接管理**
  - 自动管理连接生命周期和资源释放
  - 智能缓冲区控制，防止内存溢出
  - 超时清理机制，自动释放不活跃连接

- **强大扩展功能**
  - 完整的事件系统，支持监听所有转发事件
  - 灵活的日志接口，支持记录详细连接和传输信息
  - 允许自定义数据处理逻辑，实现协议转换和内容修改

## 安装

```bash
composer require tourze/workerman-connection-pipe
```

## 快速开始

### 配置

首先，配置依赖容器，设置日志记录器和事件分发器：

```php
use Tourze\Workerman\ConnectionPipe\Container;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Symfony\Component\EventDispatcher\EventDispatcher;

// 配置日志系统
$logger = new Logger('pipe');
$logger->pushHandler(new StreamHandler('path/to/your/app.log', Logger::DEBUG));
Container::$logger = $logger;

// 配置事件分发器
$eventDispatcher = new EventDispatcher();
Container::$eventDispatcher = $eventDispatcher;
```

### TCP 到 TCP 代理示例

创建一个简单的 TCP 代理，将本地 8443 端口的流量转发到远程服务器：

```php
use Workerman\Worker;
use Workerman\Connection\TcpConnection;
use Workerman\Connection\AsyncTcpConnection;
use Tourze\Workerman\ConnectionPipe\PipeFactory;

// 创建本地监听服务
$worker = new Worker('tcp://0.0.0.0:8443');

$worker->onConnect = function(TcpConnection $connection) {
    // 创建到目标服务器的连接
    $targetConnection = new AsyncTcpConnection('tcp://example.com:443');
    $targetConnection->connect();

    // 创建并启动从源到目标的管道
    $forwardPipe = PipeFactory::createTcpToTcp($connection, $targetConnection);
    $forwardPipe->pipe();

    // 创建并启动从目标到源的反向管道
    $backwardPipe = PipeFactory::createTcpToTcp($targetConnection, $connection);
    $backwardPipe->pipe();

    // 保存管道引用以便后续清理
    $connection->pipeTo = $forwardPipe;
    $connection->pipeFrom = $backwardPipe;

    // 设置源连接关闭时的清理逻辑
    $connection->onClose = function() use ($connection) {
        if (isset($connection->pipeTo)) {
            $connection->pipeTo->unpipe();
        }
        if (isset($connection->pipeFrom)) {
            $connection->pipeFrom->unpipe();
        }
    };
};

Worker::runAll();
```

### UDP 到 UDP 转发示例

创建一个 UDP 转发服务，将本地 8053 端口的 UDP 流量转发到 Google 的 DNS 服务器：

```php
use Workerman\Worker;
use Workerman\Connection\AsyncUdpConnection;
use Tourze\Workerman\ConnectionPipe\PipeFactory;

// 创建 UDP 源服务器
$sourceWorker = new Worker('udp://0.0.0.0:8053');

$sourceWorker->onWorkerStart = function($worker) {
    // 创建到目标 DNS 服务器的 UDP 连接
    $targetConnection = new AsyncUdpConnection('udp://8.8.8.8:53');

    $targetConnection->onConnect = function($connection) use ($worker) {
        echo "已连接到目标 UDP 服务器\n";
        // 存储目标连接
        $worker->targetConnection = $connection;

        // 获取 UDP 连接对象
        $sourceConnection = $worker->getMainSocket();

        // 创建 UDP 到 UDP 管道
        $pipe = PipeFactory::createUdpToUdp($sourceConnection, $connection);

        // 存储管道引用
        $worker->pipe = $pipe;

        // 启动管道
        $pipe->pipe();
    };

    $targetConnection->connect();
};

$sourceWorker->onMessage = function($connection, $data, $sourceAddress, $sourcePort) {
    $worker = $connection->worker;

    // 检查目标连接是否可用
    if (!isset($worker->pipe)) {
        return;
    }

    // 转发数据，包含源地址信息
    $worker->pipe->forward($data, $sourceAddress, $sourcePort);
};

Worker::runAll();
```

## 监听事件

您可以监听数据转发相关的事件，进行自定义处理：

```php
use Tourze\Workerman\ConnectionPipe\Event\DataForwardedEvent;
use Tourze\Workerman\ConnectionPipe\Event\ForwardFailedEvent;
use Tourze\Workerman\ConnectionPipe\Container;

// 监听数据转发成功事件
Container::$eventDispatcher->addListener(
    DataForwardedEvent::class, 
    function(DataForwardedEvent $event) {
        $pipe = $event->getPipe();
        $data = $event->getData();
        $sourceProtocol = $event->getSourceProtocol();
        $targetProtocol = $event->getTargetProtocol();
        $context = $event->getContext();

        // 处理成功转发的数据，例如统计或记录
        echo "转发 " . strlen($data) . " 字节，从 {$sourceProtocol} 到 {$targetProtocol}\n";
    }
);

// 监听数据转发失败事件
Container::$eventDispatcher->addListener(
    ForwardFailedEvent::class,
    function(ForwardFailedEvent $event) {
        $errorMessage = $event->getErrorMessage();

        // 处理转发失败，例如警告或重试
        echo "转发失败: {$errorMessage}\n";
    }
);
```

## 自定义数据处理

如果需要在转发过程中修改或处理数据，可以继承现有管道类并重写相关方法：

```php
use Tourze\Workerman\ConnectionPipe\Pipe\TcpToTcpPipe;

class EncryptedTcpPipe extends TcpToTcpPipe
{
    public function forward(string $data, string $sourceAddress = '', int $sourcePort = 0): bool
    {
        // 在转发前对数据进行加密或处理
        $processedData = $this->encryptData($data);

        // 调用父类方法完成实际转发
        return parent::forward($processedData, $sourceAddress, $sourcePort);
    }

    protected function encryptData(string $data): string
    {
        // 实现您的数据加密或处理逻辑
        return $data; // 示例中未做实际处理
    }
}

// 使用自定义管道类
$pipe = new EncryptedTcpPipe();
$pipe->setSource($sourceConnection);
$pipe->setTarget($targetConnection);
$pipe->pipe();
```

## 详细工作流程

每种连接组合的详细工作流程，请参阅 [workflows](./workflows) 目录：

- [TCP 到 TCP 转发流程](./workflows/tcp_to_tcp_workflow.md)
- [TCP 到 UDP 转发流程](./workflows/tcp_to_udp_workflow.md)
- [UDP 到 TCP 转发流程](./workflows/udp_to_tcp_workflow.md)
- [UDP 到 UDP 转发流程](./workflows/udp_to_udp_workflow.md)

## 高级配置

### 设置缓冲区大小

对于大流量应用，可以调整 TCP 连接的缓冲区大小：

```php
use Workerman\Connection\TcpConnection;

// 全局设置最大发送缓冲区大小为 10MB
TcpConnection::$defaultMaxSendBufferSize = 10 * 1024 * 1024;

// 设置单个连接的缓冲区大小
$targetConnection->maxSendBufferSize = 5 * 1024 * 1024;
```

### 启用加密传输

可以在 TCP 连接上启用 SSL/TLS 加密：

```php
$targetConnection = new AsyncTcpConnection('tcp://example.com:443');

// 启用 SSL
$targetConnection->transport = 'ssl';

$targetConnection->connect();
```

## 性能优化建议

1. **连接复用**：对频繁建立的连接，考虑实现连接池，降低连接建立开销。

2. **多进程模式**：在多核服务器上，可分配适当数量的 Worker 进程：

   ```php
   $worker->count = 4; // 根据 CPU 核心数和负载调整
   ```

3. **内存优化**：对于长时间运行的应用，定期检查内存使用情况，防止内存泄漏。

4. **超时设置**：为不同应用场景设置合适的超时值，确保资源能及时释放。

## 问题排查

1. **连接无法建立**：检查网络连接、防火墙设置，以及目标服务器是否可达。

2. **数据转发失败**：确保管道正确创建和启动，检查事件监听器中的错误信息。

3. **内存使用过高**：检查连接是否正确关闭，NAT 映射是否定期清理。

4. **UDP 通信不稳定**：UDP 没有连接状态，确保正确处理数据包丢失和乱序情况。

## 贡献

请查看 [CONTRIBUTING.md](CONTRIBUTING.md) 了解我们的行为准则和提交拉取请求的流程详情。

## 更新日志

查看 [CHANGELOG.md](CHANGELOG.md) 了解每个版本的详细更改内容。

## 许可证

本项目采用 [MIT 许可证](LICENSE)。
