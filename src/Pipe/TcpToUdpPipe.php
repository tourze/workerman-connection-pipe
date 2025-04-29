<?php

namespace Tourze\Workerman\ConnectionPipe\Pipe;

use Tourze\Workerman\ConnectionPipe\Event\DataForwardedEvent;
use Tourze\Workerman\ConnectionPipe\Event\ForwardFailedEvent;
use Workerman\Connection\ConnectionInterface;
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
    public function setSource(ConnectionInterface $connection): void
    {
        if (!($connection instanceof TcpConnection)) {
            throw new \InvalidArgumentException("源连接必须是 TcpConnection 类型");
        }

        parent::setSource($connection);
    }

    public function setTarget(ConnectionInterface $connection): void
    {
        if (!($connection instanceof UdpConnection)) {
            throw new \InvalidArgumentException("目标连接必须是 UdpConnection 类型");
        }

        parent::setTarget($connection);
    }

    protected function setupPipeCallbacks(): void
    {
        // 在源连接上设置onMessage回调，将数据转发到目标连接
        $this->source->onMessage = function (ConnectionInterface $conn, $data) {
            $this->forward($data);
        };

        // 在源连接上设置onClose回调，关闭目标连接
        $this->source->onClose = function () {
            $this->target->close();
        };
    }

    public function forward(string $data, string $sourceAddress = '', int $sourcePort = 0): bool
    {
        if (!$this->isActive || !$this->target || !$this->source) {
            return false;
        }

        try {
            $this->logger->debug("TCP->UDP 数据转发: {$this->getId()}", [
                'dataLength' => strlen($data),
                'sourceId' => $this->source->id,
                'targetLocalAddress' => $this->target->getLocalAddress(),
                'targetAddress' => $this->target->getRemoteIp(),
                'targetPort' => $this->target->getRemotePort(),
            ]);

            $result = $this->target->send($data);

            if ($result === false) {
                $this->logger->error("TCP->UDP 数据转发失败: {$this->getId()}", [
                    'dataLength' => strlen($data),
                    'sourceId' => $this->source->id,
                    'targetLocalAddress' => $this->target->getLocalAddress(),
                    'targetAddress' => $this->target->getRemoteIp(),
                    'targetPort' => $this->target->getRemotePort(),
                ]);

                // 分发转发失败事件
                $failedEvent = new ForwardFailedEvent($this, $data, 'TCP', 'UDP', "发送失败");
                $this->eventDispatcher->dispatch($failedEvent);

                return false;
            }

            // 分发转发成功事件
            $forwardedEvent = new DataForwardedEvent($this, $data, 'TCP', 'UDP', [
                'targetAddress' => $this->target->getRemoteIp(),
                'targetPort' => $this->target->getRemotePort(),
            ]);
            $this->eventDispatcher->dispatch($forwardedEvent);

            return true;
        } catch (\Throwable $e) {
            $this->logger->error("TCP->UDP 数据转发异常: {$this->getId()}", [
                'exception' => $e->getMessage(),
                'sourceId' => $this->source->id,
                'targetLocalAddress' => $this->target->getLocalAddress(),
            ]);

            // 分发转发失败事件
            $failedEvent = new ForwardFailedEvent($this, $data, 'TCP', 'UDP', $e->getMessage());
            $this->eventDispatcher->dispatch($failedEvent);

            return false;
        }
    }
}
