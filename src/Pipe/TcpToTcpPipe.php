<?php

namespace Tourze\Workerman\ConnectionPipe\Pipe;

use Tourze\Workerman\ConnectionPipe\DTO\ForwardContext;
use Workerman\Connection\TcpConnection;

/**
 * TCP到TCP连接管道
 * 用于在两个TCP连接之间转发数据
 *
 * @property TcpConnection $source
 * @property TcpConnection $target
 */
class TcpToTcpPipe extends AbstractConnectionPipe
{
    /**
     * 获取期望的源连接类型
     */
    protected function getExpectedSourceType(): string
    {
        return 'tcp';
    }

    /**
     * 获取期望的目标连接类型
     */
    protected function getExpectedTargetType(): string
    {
        return 'tcp';
    }

    public function forward(string $data, string $sourceAddress = '', int $sourcePort = 0): bool
    {
        $context = new ForwardContext(
            sourceId: $this->source->id ?? null,
            targetId: $this->target->id ?? null,
            sourceAddress: $this->source->getLocalAddress(),
            sourcePort: $this->source->getLocalPort(),
            targetAddress: $this->target->getRemoteAddress(),
        );

        return $this->doForward($data, $context);
    }
}
