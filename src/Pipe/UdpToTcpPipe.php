<?php

namespace Tourze\Workerman\ConnectionPipe\Pipe;

use Tourze\Workerman\ConnectionPipe\Event\DataForwardedEvent;
use Tourze\Workerman\ConnectionPipe\Event\ForwardFailedEvent;
use Workerman\Connection\ConnectionInterface;
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
    public function setSource(ConnectionInterface $connection): void
    {
        if (!($connection instanceof UdpConnection)) {
            throw new \InvalidArgumentException("源连接必须是 UdpConnection 类型");
        }

        parent::setSource($connection);
    }

    public function setTarget(ConnectionInterface $connection): void
    {
        if (!($connection instanceof TcpConnection)) {
            throw new \InvalidArgumentException("目标连接必须是 TcpConnection 类型");
        }

        parent::setTarget($connection);
    }

    protected function setupPipeCallbacks(): void
    {
        // 在UDP源连接上设置onMessage回调
        $this->source->onMessage = function ($connection, $data) {
            if (empty($this->messageWatcher)) {
                $this->forward($data);
                return;
            }
            $this->messageWatcher->__invoke(
                $data,
                $this->source,
                $this->target,
                function (bool $result) use ($data) {
                    if ($result) {
                        $this->forward($data);
                    }
                },
            );
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
            $this->logger->debug("UDP->TCP 数据转发: {$this->getId()}", [
                'dataLength' => strlen($data),
                'sourceAddress' => $this->source->getLocalAddress(),
                'sourcePort' => $this->source->getLocalPort(),
                'sourceLocalAddress' => $this->source->getLocalAddress(),
                'targetAddress' => $this->target->getRemoteAddress(),
            ]);

            // 发送到TCP连接
            $result = $this->target->send($data);

            if ($result === false) {
                $this->logger->error("UDP->TCP 数据转发失败: {$this->getId()}", [
                    'dataLength' => strlen($data),
                    'sourceAddress' => $this->source->getLocalAddress(),
                    'sourcePort' => $this->source->getLocalPort(),
                    'sourceLocalAddress' => $this->source->getLocalAddress(),
                    'targetAddress' => $this->target->getRemoteAddress(),
                ]);

                // 分发转发失败事件
                $failedEvent = new ForwardFailedEvent($this, $data, 'UDP', 'TCP', "发送失败");
                $this->eventDispatcher->dispatch($failedEvent);

                return false;
            }

            // 分发转发成功事件
            $forwardedEvent = new DataForwardedEvent($this, $data, 'UDP', 'TCP', [
                'sourceAddress' => $this->source->getLocalAddress(),
                'sourcePort' => $this->source->getLocalPort(),
                'targetAddress' => $this->target->getRemoteAddress(),
            ]);
            $this->eventDispatcher->dispatch($forwardedEvent);

            return true;
        } catch (\Throwable $e) {
            $this->logger->error("UDP->TCP 数据转发异常: {$this->getId()}", [
                'exception' => $e->getMessage(),
                'sourceAddress' => $this->source->getLocalAddress(),
                'sourcePort' => $this->source->getLocalPort(),
                'sourceLocalAddress' => $this->source->getLocalAddress(),
                'targetAddress' => $this->target->getRemoteAddress(),
            ]);

            // 分发转发失败事件
            $failedEvent = new ForwardFailedEvent($this, $data, 'UDP', 'TCP', $e->getMessage());
            $this->eventDispatcher->dispatch($failedEvent);

            return false;
        }
    }
}
