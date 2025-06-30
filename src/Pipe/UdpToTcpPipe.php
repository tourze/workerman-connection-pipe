<?php

namespace Tourze\Workerman\ConnectionPipe\Pipe;

use Tourze\Workerman\ConnectionPipe\DTO\ForwardContext;
use Workerman\Connection\TcpConnection;
use Workerman\Connection\UdpConnection;

/**
 * UDP到TCP连接管道
 * 用于将UDP连接的数据转发到TCP连接
 *
 * @property UdpConnection $source
 * @property TcpConnection $target
 */
class UdpToTcpPipe extends AbstractConnectionPipe
{
    /**
     * 获取期望的源连接类型
     */
    protected function getExpectedSourceType(): string
    {
        return 'udp';
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
            sourceAddress: $sourceAddress !== '' ? $sourceAddress : $this->source->getLocalAddress(),
            sourcePort: $sourcePort !== 0 ? $sourcePort : $this->source->getLocalPort(),
            sourceLocalAddress: $this->source->getLocalAddress(),
            targetAddress: $this->target->getRemoteAddress(),
        );

        return $this->doForward($data, $context);
    }
}
