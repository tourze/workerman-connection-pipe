<?php

namespace Tourze\Workerman\ConnectionPipe\Tests\Unit\Pipe;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Tourze\Workerman\ConnectionPipe\Pipe\AbstractConnectionPipe;
use Tourze\Workerman\ConnectionPipe\Watcher\MessageWatcherInterface;
use Workerman\Connection\ConnectionInterface;

class AbstractConnectionPipeTest extends TestCase
{
    /**
     * 抽象管道的具体实现（用于测试）
     */
    private function createConcreteConnectionPipe(
        ?EventDispatcherInterface $eventDispatcher = null,
        ?LoggerInterface          $logger = null
    ): AbstractConnectionPipe
    {
        return new class($eventDispatcher, $logger) extends AbstractConnectionPipe {
            protected function setupPipeCallbacks(): void
            {
                // 测试实现，不做任何操作
            }

            public function forward(string $data, string $sourceAddress = '', int $sourcePort = 0): bool
            {
                // 测试实现，始终返回成功
                return true;
            }
        };
    }

    /**
     * 测试构造函数和ID生成
     */
    public function testConstructorAndId(): void
    {
        $pipe = $this->createConcreteConnectionPipe();

        // 测试ID是否为非空字符串
        $this->assertIsString($pipe->getId());
        $this->assertNotEmpty($pipe->getId());
        $this->assertStringStartsWith('pipe_', $pipe->getId());
    }

    /**
     * 测试设置和获取源/目标连接
     */
    public function testSetGetSourceTarget(): void
    {
        $pipe = $this->createConcreteConnectionPipe();

        // 创建模拟连接
        /** @var ConnectionInterface $sourceConnection */
        $sourceConnection = $this->createMock(ConnectionInterface::class);
        /** @var ConnectionInterface $targetConnection */
        $targetConnection = $this->createMock(ConnectionInterface::class);

        // 设置连接
        $pipe->setSource($sourceConnection);
        $pipe->setTarget($targetConnection);

        // 验证获取的连接
        $this->assertSame($sourceConnection, $pipe->getSource());
        $this->assertSame($targetConnection, $pipe->getTarget());
    }

    /**
     * 测试管道状态管理
     */
    public function testPipeStatus(): void
    {
        $pipe = $this->createConcreteConnectionPipe();

        // 测试初始状态
        $this->assertFalse($pipe->isActive());

        // 设置源和目标连接
        /** @var ConnectionInterface $sourceConnection */
        $sourceConnection = $this->createMock(ConnectionInterface::class);
        /** @var ConnectionInterface $targetConnection */
        $targetConnection = $this->createMock(ConnectionInterface::class);
        $pipe->setSource($sourceConnection);
        $pipe->setTarget($targetConnection);

        // 测试启动管道后的状态
        $pipe->pipe();
        $this->assertTrue($pipe->isActive());

        // 测试停止管道后的状态
        $pipe->unpipe();
        $this->assertFalse($pipe->isActive());
    }

    /**
     * 测试设置和清除消息观察器
     */
    public function testMessageWatcher(): void
    {
        $pipe = $this->createConcreteConnectionPipe();

        // 创建消息观察器
        /** @var MessageWatcherInterface $watcher */
        $watcher = $this->createMock(MessageWatcherInterface::class);

        // 设置观察器
        $pipe->setMessageWatcher($watcher);

        // 清除观察器
        $pipe->unsetMessageWatcher();

        // 这里我们无法直接测试观察器是否已设置或清除，因为它是一个protected属性
        // 但至少可以确保上述方法调用不会抛出异常
        $this->assertTrue(true);
    }

    /**
     * 测试获取协议信息
     */
    public function testGetProtocols(): void
    {
        $pipe = $this->createConcreteConnectionPipe();

        // 验证默认协议信息
        $protocols = $pipe->getProtocols();
        $this->assertIsArray($protocols);
        $this->assertArrayHasKey('source', $protocols);
        $this->assertArrayHasKey('target', $protocols);
    }
}
