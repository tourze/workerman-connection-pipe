<?php

namespace Tourze\Workerman\ConnectionPipe\Pipe;

use Tourze\Workerman\ConnectionPipe\Event\DataForwardedEvent;
use Tourze\Workerman\ConnectionPipe\Event\ForwardFailedEvent;
use Workerman\Connection\ConnectionInterface;
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
    public function setSource(ConnectionInterface $connection): void
    {
        if (!($connection instanceof UdpConnection)) {
            throw new \InvalidArgumentException("源连接必须是 UdpConnection 类型");
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
        // 在UDP源连接上设置onMessage回调
        $this->source->onMessage = function ($connection, $data, $sourceAddress, $sourcePort) {
            // 执行转发
            $this->forward($data, $sourceAddress, $sourcePort);
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
            $this->logger->debug("UDP->UDP 数据转发: {$this->getId()}", [
                'dataLength' => strlen($data),
                'sourceAddress' => $sourceAddress,
                'sourcePort' => $sourcePort,
                'sourceLocalAddress' => $this->source->getLocalAddress(),
                'targetLocalAddress' => $this->target->getLocalAddress(),
                'targetAddress' => $this->target->getRemoteAddress(),
                'targetPort' => $this->target->getRemotePort(),
            ]);

            $result = $this->target->send($data);

            if ($result === false) {
                $this->logger->error("UDP->UDP 数据转发失败: {$this->getId()}", [
                    'dataLength' => strlen($data),
                    'sourceAddress' => $sourceAddress,
                    'sourcePort' => $sourcePort,
                    'sourceLocalAddress' => $this->source->getLocalAddress(),
                    'targetLocalAddress' => $this->target->getLocalAddress(),
                    'targetAddress' => $this->target->getRemoteAddress(),
                    'targetPort' => $this->target->getRemotePort(),
                ]);

                // 分发转发失败事件
                $failedEvent = new ForwardFailedEvent($this, $data, 'UDP', 'UDP', "发送失败");
                $this->eventDispatcher->dispatch($failedEvent);

                return false;
            }

            // 分发转发成功事件
            $forwardedEvent = new DataForwardedEvent($this, $data, 'UDP', 'UDP', [
                'sourceAddress' => $sourceAddress,
                'sourcePort' => $sourcePort,
                'targetAddress' => $this->target->getRemoteAddress(),
                'targetPort' => $this->target->getRemotePort(),
            ]);
            $this->eventDispatcher->dispatch($forwardedEvent);

            return true;
        } catch (\Throwable $e) {
            $this->logger->error("UDP->UDP 数据转发异常: {$this->getId()}", [
                'exception' => $e->getMessage(),
                'sourceAddress' => $sourceAddress,
                'sourcePort' => $sourcePort,
                'sourceLocalAddress' => $this->source->getLocalAddress(),
                'targetLocalAddress' => $this->target->getLocalAddress(),
            ]);

            // 分发转发失败事件
            $failedEvent = new ForwardFailedEvent($this, $data, 'UDP', 'UDP', $e->getMessage());
            $this->eventDispatcher->dispatch($failedEvent);

            return false;
        }
    }
}
