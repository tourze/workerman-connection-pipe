<?php

namespace Tourze\Workerman\ConnectionPipe\Event;

use Symfony\Contracts\EventDispatcher\Event;
use Tourze\Workerman\ConnectionPipe\Contracts\ConnectionPipeInterface;

/**
 * 数据转发失败事件
 */
class ForwardFailedEvent extends Event
{
    /**
     * @param ConnectionPipeInterface $pipe           连接管道实例
     * @param string                  $data           转发的数据
     * @param string                  $sourceProtocol 源协议
     * @param string                  $targetProtocol 目标协议
     * @param string                  $reason         失败原因
     * @param array<string, mixed>    $metadata       其他元数据信息
     */
    public function __construct(
        private readonly ConnectionPipeInterface $pipe,
        private readonly string $data,
        private readonly string $sourceProtocol,
        private readonly string $targetProtocol,
        private readonly string $reason,
        private readonly array $metadata = [],
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
     * 获取失败原因
     */
    public function getReason(): string
    {
        return $this->reason;
    }

    /**
     * 获取元数据
     *
     * @return array<string, mixed>
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }
}
