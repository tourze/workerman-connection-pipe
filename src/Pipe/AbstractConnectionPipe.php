<?php

namespace Tourze\Workerman\ConnectionPipe\Pipe;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Tourze\Workerman\ConnectionPipe\Container;
use Tourze\Workerman\ConnectionPipe\Contracts\ConnectionPipeInterface;
use Tourze\Workerman\ConnectionPipe\DTO\ForwardContext;
use Tourze\Workerman\ConnectionPipe\Event\DataForwardedEvent;
use Tourze\Workerman\ConnectionPipe\Event\ForwardFailedEvent;
use Tourze\Workerman\ConnectionPipe\Watcher\MessageWatcherInterface;
use Workerman\Connection\ConnectionInterface;
use Workerman\Connection\TcpConnection;
use Workerman\Connection\UdpConnection;

/**
 * 抽象连接管道实现
 * 提供通用的管道功能实现
 */
abstract class AbstractConnectionPipe implements ConnectionPipeInterface
{
    /**
     * 源连接
     */
    protected ConnectionInterface $source;

    /**
     * 目标连接
     */
    protected ConnectionInterface $target;

    /**
     * 管道ID
     */
    protected string $id;

    /**
     * 管道是否处于活动状态
     */
    protected bool $isActive = false;

    /**
     * 管道的协议信息
     */
    protected array $protocols = [
        'source' => '',
        'target' => ''
    ];

    protected EventDispatcherInterface $eventDispatcher;

    protected LoggerInterface $logger;

    /**
     * 构造函数
     */
    public function __construct(
        ?EventDispatcherInterface $eventDispatcher = null,
        ?LoggerInterface $logger = null,
    )
    {
        // 生成唯一ID
        $this->id = uniqid('pipe_', true);

        $this->eventDispatcher = $eventDispatcher ?? Container::getInstance()->getEventDispatcher() ?? new EventDispatcher();
        $this->logger = $logger ?? Container::getInstance()->getLogger() ?? new NullLogger();
    }

    public function getSource(): ConnectionInterface
    {
        return $this->source;
    }

    public function getTarget(): ConnectionInterface
    {
        return $this->target;
    }

    /**
     * 设置源连接的基础实现
     */
    protected function doSetSource(ConnectionInterface $connection): void
    {
        $this->source = $connection;

        // 确定源协议
        $this->protocols['source'] = $connection instanceof TcpConnection ? 'tcp' : 'udp';
    }

    /**
     * 设置目标连接的基础实现
     */
    protected function doSetTarget(ConnectionInterface $connection): void
    {
        $this->target = $connection;

        // 确定目标协议
        $this->protocols['target'] = $connection instanceof TcpConnection ? 'tcp' : 'udp';
    }

    public function setSource(ConnectionInterface $connection): void
    {
        $this->validateConnectionType($connection, $this->getExpectedSourceType(), 'Source');
        $this->doSetSource($connection);
    }

    public function setTarget(ConnectionInterface $connection): void
    {
        $this->validateConnectionType($connection, $this->getExpectedTargetType(), 'Target');
        $this->doSetTarget($connection);
    }

    public function pipe(): self
    {
        if ($this->isActive) {
            return $this;
        }

        // 设置管道回调，直接覆盖现有回调
        $this->setupPipeCallbacks();

        $this->isActive = true;

        $this->logger->info("连接管道已启动: {$this->getId()}", [
            'protocols' => $this->getProtocols(),
        ]);

        return $this;
    }

    public function unpipe(): self
    {
        if (!$this->isActive) {
            return $this;
        }

        $this->isActive = false;

        $this->logger->info("连接管道已停止: {$this->getId()}", [
            'protocols' => $this->getProtocols(),
        ]);

        return $this;
    }

    public function getProtocols(): array
    {
        return $this->protocols;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function getId(): string
    {
        return $this->id;
    }

    protected ?MessageWatcherInterface $messageWatcher = null;

    public function setMessageWatcher(?MessageWatcherInterface $messageWatcher): void
    {
        $this->messageWatcher = $messageWatcher;
    }

    public function unsetMessageWatcher(): void
    {
        $this->messageWatcher = null;
    }

    /**
     * 转发数据的抽象方法，由具体子类实现
     *
     * @param string $data 要转发的数据
     * @param string $sourceAddress 源地址(UDP)
     * @param int $sourcePort 源端口(UDP)
     * @return bool 成功返回true，失败返回false
     */
    abstract public function forward(string $data, string $sourceAddress = '', int $sourcePort = 0): bool;

    /**
     * 获取期望的源连接类型
     *
     * @return string 'tcp' 或 'udp'
     */
    abstract protected function getExpectedSourceType(): string;

    /**
     * 获取期望的目标连接类型
     *
     * @return string 'tcp' 或 'udp'
     */
    abstract protected function getExpectedTargetType(): string;

    /**
     * 验证连接类型
     *
     * @param ConnectionInterface $connection
     * @param string $expectedType
     * @param string $connectionName
     * @throws \InvalidArgumentException
     */
    protected function validateConnectionType(ConnectionInterface $connection, string $expectedType, string $connectionName): void
    {
        $actualType = $connection instanceof TcpConnection ? 'tcp' : 'udp';

        if ($actualType !== $expectedType) {
            $expectedClass = $expectedType === 'tcp' ? TcpConnection::class : UdpConnection::class;
            throw new \InvalidArgumentException(
                sprintf('%s connection must be an instance of %s', $connectionName, $expectedClass)
            );
        }
    }

    /**
     * 设置通用的管道回调
     */
    protected function setupPipeCallbacks(): void
    {
        // 设置源连接的 onMessage 回调
        $this->setupSourceOnMessage();
        
        // 设置源连接的 onClose 回调
        $this->source->onClose = function () {
            $this->target->close();
        };

        // 如果目标是 TCP 连接，设置缓冲区管理
        if ($this->target instanceof TcpConnection) {
            $this->setupTargetBufferCallbacks();
        }
    }

    /**
     * 设置源连接的 onMessage 回调
     * 子类可以重写此方法以自定义行为
     */
    protected function setupSourceOnMessage(): void
    {
        if ($this->source instanceof UdpConnection) {
            $this->source->onMessage = function ($conn, $data, $sourceAddress, $sourcePort) {
                $this->handleMessage($data, $sourceAddress, $sourcePort);
            };
        } else {
            $this->source->onMessage = function ($conn, $data) {
                $this->handleMessage($data);
            };
        }
    }

    /**
     * 处理接收到的消息
     */
    protected function handleMessage(string $data, string $sourceAddress = '', int $sourcePort = 0): void
    {
        if (empty($this->messageWatcher)) {
            $this->forward($data, $sourceAddress, $sourcePort);
            return;
        }

        $this->messageWatcher->__invoke(
            $data,
            $this->source,
            $this->target,
            function (bool $result) use ($data, $sourceAddress, $sourcePort) {
                if ($result) {
                    $this->forward($data, $sourceAddress, $sourcePort);
                }
            },
        );
    }

    /**
     * 设置目标连接的缓冲区回调（仅用于 TCP 目标）
     */
    protected function setupTargetBufferCallbacks(): void
    {
        $target = $this->target;
        assert($target instanceof TcpConnection);;

        // 在目标连接上设置onBufferFull回调，暂停源连接接收数据
        $target->onBufferFull = function () {
            if ($this->source instanceof TcpConnection) {
                $this->source->pauseRecv();
            }
        };

        // 在目标连接上设置onBufferDrain回调，恢复源连接接收数据
        $target->onBufferDrain = function () {
            if ($this->source instanceof TcpConnection) {
                $this->source->resumeRecv();
            }
        };
    }

    /**
     * 执行数据转发的通用逻辑
     */
    protected function doForward(string $data, ForwardContext $context): bool
    {
        if (!$this->isActive()) {
            return false;
        }

        try {
            // 记录调试日志
            $this->logger->debug($this->getForwardLogMessage(), array_merge([
                'size' => strlen($data),
                'pipe_id' => $this->id,
            ], $context->toArray()));

            // 发送数据
            $sendResult = $this->target->send($data);

            if ($sendResult === false) {
                $this->handleForwardFailure('发送失败', $data, $context);
                return false;
            }

            // 分发成功事件
            $this->dispatchForwardedEvent($data, $context);
            return true;

        } catch (\Throwable $e) {
            $this->handleForwardError($e, $data, $context);
            return false;
        }
    }

    /**
     * 获取转发日志消息
     */
    protected function getForwardLogMessage(): string
    {
        return sprintf(
            '%s->%s 数据转发: %s',
            $this->protocols['source'],
            $this->protocols['target'],
            $this->id
        );
    }

    /**
     * 处理转发失败
     */
    protected function handleForwardFailure(string $reason, string $data, ForwardContext $context): void
    {
        $this->logger->error($this->getForwardLogMessage() . ' 失败', array_merge([
            'reason' => $reason,
            'size' => strlen($data),
            'pipe_id' => $this->id,
        ], $context->toArray()));

        $this->dispatchFailedEvent($reason, $data, $context);
    }

    /**
     * 处理转发错误
     */
    protected function handleForwardError(\Throwable $e, string $data, ForwardContext $context): void
    {
        $this->logger->error($this->getForwardLogMessage() . ' 异常', array_merge([
            'exception' => $e->getMessage(),
            'size' => strlen($data),
            'pipe_id' => $this->id,
        ], $context->toArray()));

        $this->dispatchFailedEvent($e->getMessage(), $data, $context);
    }

    /**
     * 分发转发成功事件
     */
    protected function dispatchForwardedEvent(string $data, ForwardContext $context): void
    {
        $event = new DataForwardedEvent(
            $this,
            $data,
            $this->protocols['source'],
            $this->protocols['target'],
            $context->toArray()
        );
        $this->eventDispatcher->dispatch($event);
    }

    /**
     * 分发转发失败事件
     */
    protected function dispatchFailedEvent(string $reason, string $data, ForwardContext $context): void
    {
        $event = new ForwardFailedEvent(
            $this,
            $data,
            $this->protocols['source'],
            $this->protocols['target'],
            $reason,
            $context->toArray()
        );
        $this->eventDispatcher->dispatch($event);
    }
}
