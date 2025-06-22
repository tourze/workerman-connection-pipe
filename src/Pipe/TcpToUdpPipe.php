<?php

namespace Tourze\Workerman\ConnectionPipe\Pipe;

use Tourze\Workerman\ConnectionPipe\DTO\ForwardContext;
use Workerman\Connection\TcpConnection;
use Workerman\Connection\UdpConnection;

/**
 * TCP到UDP连接管道
 * 用于将TCP连接数据转发到UDP连接
 *
 * @property TcpConnection $source
 * @property UdpConnection $target
 */
class TcpToUdpPipe extends AbstractConnectionPipe
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
        return 'udp';
    }

    public function forward(string $data, string $sourceAddress = '', int $sourcePort = 0): bool
    {
        $context = new ForwardContext(
            sourceId: isset($this->source->id) ? (string)$this->source->id : null,
            targetLocalAddress: $this->target->getLocalAddress(),
            targetAddress: $this->target->getRemoteIp(),
            targetPort: $this->target->getRemotePort(),
        );

        return $this->doForward($data, $context);
    }
}
