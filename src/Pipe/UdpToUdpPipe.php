<?php

namespace Tourze\Workerman\ConnectionPipe\Pipe;

use Tourze\Workerman\ConnectionPipe\DTO\ForwardContext;
use Workerman\Connection\UdpConnection;

/**
 * UDP到UDP连接管道
 * 用于在两个UDP连接之间转发数据
 *
 * @property UdpConnection $source
 * @property UdpConnection $target
 */
class UdpToUdpPipe extends AbstractConnectionPipe
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
        return 'udp';
    }

    public function forward(string $data, string $sourceAddress = '', int $sourcePort = 0): bool
    {
        $context = new ForwardContext(
            sourceAddress: $sourceAddress,
            sourcePort: $sourcePort,
            sourceLocalAddress: $this->source->getLocalAddress(),
            targetLocalAddress: $this->target->getLocalAddress(),
            targetAddress: $this->target->getRemoteAddress(),
            targetPort: $this->target->getRemotePort(),
        );

        return $this->doForward($data, $context);
    }
}
