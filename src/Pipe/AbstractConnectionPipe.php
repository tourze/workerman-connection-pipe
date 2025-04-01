<?php

namespace Tourze\Workerman\ConnectionPipe\Pipe;

use Tourze\Workerman\ConnectionPipe\Container;
use Tourze\Workerman\ConnectionPipe\Contracts\ConnectionPipeInterface;
use Workerman\Connection\ConnectionInterface;
use Workerman\Connection\TcpConnection;

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

    /**
     * 构造函数
     */
    public function __construct()
    {
        // 生成唯一ID
        $this->id = uniqid('pipe_', true);
    }

    public function getSource(): ConnectionInterface
    {
        return $this->source;
    }

    public function getTarget(): ConnectionInterface
    {
        return $this->target;
    }

    public function setSource(ConnectionInterface $connection): void
    {
        $this->source = $connection;

        // 确定源协议
        $this->protocols['source'] = $connection instanceof TcpConnection ? 'tcp' : 'udp';
    }

    public function setTarget(ConnectionInterface $connection): void
    {
        $this->target = $connection;

        // 确定目标协议
        $this->protocols['target'] = $connection instanceof TcpConnection ? 'tcp' : 'udp';
    }

    public function pipe(): self
    {
        if ($this->isActive) {
            return $this;
        }

        // 设置管道回调，直接覆盖现有回调
        $this->setupPipeCallbacks();

        $this->isActive = true;

        Container::getLogger()?->info("连接管道已启动: {$this->getId()}", [
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

        Container::getLogger()?->info("连接管道已停止: {$this->getId()}", [
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

    /**
     * 设置管道回调，类似于TcpConnection::pipe方法
     * 直接覆盖现有回调
     */
    abstract protected function setupPipeCallbacks(): void;

    /**
     * 转发数据的抽象方法，由具体子类实现
     *
     * @param string $data 要转发的数据
     * @param string $sourceAddress 源地址(UDP)
     * @param int $sourcePort 源端口(UDP)
     * @return bool 成功返回true，失败返回false
     */
    abstract public function forward(string $data, string $sourceAddress = '', int $sourcePort = 0): bool;
}
