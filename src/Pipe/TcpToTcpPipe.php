<?php

namespace Tourze\Workerman\ConnectionPipe\Pipe;

use Tourze\Workerman\ConnectionPipe\Event\DataForwardedEvent;
use Workerman\Connection\ConnectionInterface;
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
    public function setSource(ConnectionInterface $connection): void
    {
        if (!($connection instanceof TcpConnection)) {
            throw new \InvalidArgumentException("源连接必须是 TcpConnection 类型");
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
        // 在源连接上设置onMessage回调，将数据转发到目标连接
        $this->source->onMessage = function (ConnectionInterface $conn, $data) {
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

        // 在目标连接上设置onBufferFull回调，暂停源连接接收数据
        $this->target->onBufferFull = function () {
            $this->source->pauseRecv();
        };

        // 在目标连接上设置onBufferDrain回调，恢复源连接接收数据
        $this->target->onBufferDrain = function () {
            $this->source->resumeRecv();
        };
    }

    public function forward(string $data, string $sourceAddress = '', int $sourcePort = 0): bool
    {
        if (!$this->isActive || !$this->target || !$this->source) {
            return false;
        }

        try {
            // 记录转发信息
            $this->logger->debug("TCP->TCP 数据转发: {$this->getId()}", [
                'dataLength' => strlen($data),
                'sourceId' => $this->source->id,
                'targetId' => $this->target->id,
            ]);

            // TCP连接直接发送数据
            $result = $this->target->send($data);

            // 检查发送结果
            if ($result === false) {
                $this->logger->error("TCP->TCP 数据转发失败: {$this->getId()}", [
                    'dataLength' => strlen($data),
                    'sourceId' => $this->source->id,
                    'targetId' => $this->target->id,
                ]);
                return false;
            }

            // 分发转发成功事件
            $forwardedEvent = new DataForwardedEvent($this, $data, 'TCP', 'TCP', [
                'sourceAddress' => $this->source->getLocalAddress(),
                'sourcePort' => $this->source->getLocalPort(),
                'targetAddress' => $this->target->getRemoteAddress(),
            ]);
            $this->eventDispatcher->dispatch($forwardedEvent);

            return true;
        } catch (\Throwable $e) {
            $this->logger->error("TCP->TCP 数据转发异常: {$this->getId()}", [
                'exception' => $e->getMessage(),
                'sourceId' => $this->source->id,
                'targetId' => $this->target->id,
            ]);
            return false;
        }
    }
}
