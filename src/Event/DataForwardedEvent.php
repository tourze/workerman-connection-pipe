<?php

namespace Tourze\Workerman\ConnectionPipe\Event;

use Symfony\Contracts\EventDispatcher\Event;
use Tourze\Workerman\ConnectionPipe\Contracts\ConnectionPipeInterface;

/**
 * 数据成功转发事件
 */
class DataForwardedEvent extends Event
{
    /**
     * @param ConnectionPipeInterface $pipe 连接管道实例
     * @param string $data 转发的数据
     * @param string $sourceProtocol 源协议
     * @param string $targetProtocol 目标协议
     * @param array $metadata 其他元数据信息
     */
    public function __construct(
        private readonly ConnectionPipeInterface $pipe,
        private readonly string $data,
        private readonly string $sourceProtocol,
        private readonly string $targetProtocol,
        private readonly array $metadata = []
    ) {
    }

    /**
     * 获取连接管道实例
     */
    public function getPipe(): ConnectionPipeInterface
    {
        return $this->pipe;
    }

    /**
     * 获取转发的数据
     */
    public function getData(): string
    {
        return $this->data;
    }

    /**
     * 获取源协议
     */
    public function getSourceProtocol(): string
    {
        return $this->sourceProtocol;
    }

    /**
     * 获取目标协议
     */
    public function getTargetProtocol(): string
    {
        return $this->targetProtocol;
    }

    /**
     * 获取元数据
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }
}
