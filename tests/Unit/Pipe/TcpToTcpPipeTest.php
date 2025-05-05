<?php

namespace Tourze\Workerman\ConnectionPipe\Tests\Unit\Pipe;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Tourze\Workerman\ConnectionPipe\Pipe\TcpToTcpPipe;
use Workerman\Connection\ConnectionInterface;
use Workerman\Connection\TcpConnection;

class TcpToTcpPipeTest extends TestCase
{
    private TcpToTcpPipe $pipe;

    protected function setUp(): void
    {
        parent::setUp();

        // 创建事件分发器和日志记录器模拟对象
        /** @var EventDispatcherInterface $eventDispatcher */
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        /** @var LoggerInterface $logger */
        $logger = $this->createMock(LoggerInterface::class);

        // 创建管道实例
        $this->pipe = new TcpToTcpPipe($eventDispatcher, $logger);
    }

    /**
     * 测试设置源连接的类型检查
     */
    public function testSetSourceTypeCheck(): void
    {
        // 模拟一个TCP连接
        /** @var TcpConnection $tcpConnection */
        $tcpConnection = $this->createMock(TcpConnection::class);

        // 设置源连接（应该不会抛出异常）
        $this->pipe->setSource($tcpConnection);

        // 验证连接已设置
        $this->assertSame($tcpConnection, $this->pipe->getSource());
    }

    /**
     * 测试设置非TCP类型的源连接时抛出异常
     */
    public function testSetSourceInvalidType(): void
    {
        // 使用通用连接接口而不是具体的TCP连接
        /** @var ConnectionInterface&MockObject $nonTcpConnection */
        $nonTcpConnection = $this->createMock(ConnectionInterface::class);

        // 期望抛出异常
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("源连接必须是 TcpConnection 类型");

        // 尝试设置非TCP连接
        $this->pipe->setSource($nonTcpConnection);
    }

    /**
     * 测试设置目标连接的类型检查
     */
    public function testSetTargetTypeCheck(): void
    {
        // 模拟一个TCP连接
        /** @var TcpConnection $tcpConnection */
        $tcpConnection = $this->createMock(TcpConnection::class);

        // 设置目标连接（应该不会抛出异常）
        $this->pipe->setTarget($tcpConnection);

        // 验证连接已设置
        $this->assertSame($tcpConnection, $this->pipe->getTarget());
    }

    /**
     * 测试设置非TCP类型的目标连接时抛出异常
     */
    public function testSetTargetInvalidType(): void
    {
        // 使用通用连接接口而不是具体的TCP连接
        /** @var ConnectionInterface&MockObject $nonTcpConnection */
        $nonTcpConnection = $this->createMock(ConnectionInterface::class);

        // 期望抛出异常
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("目标连接必须是 TcpConnection 类型");

        // 尝试设置非TCP连接
        $this->pipe->setTarget($nonTcpConnection);
    }

    /**
     * 测试管道的设置回调方法
     */
    public function testSetupPipeCallbacks(): void
    {
        // 创建源和目标TCP连接
        /** @var TcpConnection $sourceConnection */
        $sourceConnection = $this->createMock(TcpConnection::class);
        /** @var TcpConnection $targetConnection */
        $targetConnection = $this->createMock(TcpConnection::class);

        // 设置连接
        $this->pipe->setSource($sourceConnection);
        $this->pipe->setTarget($targetConnection);

        // 激活管道，这将调用setupPipeCallbacks
        $this->pipe->pipe();

        // 验证管道处于活动状态
        $this->assertTrue($this->pipe->isActive());
    }

    /**
     * 测试管道的转发功能
     */
    public function testForward(): void
    {
        // 简化版测试，不使用method和expects
        // 创建源和目标TCP连接
        /** @var TcpConnection&MockObject $sourceConnection */
        $sourceConnection = $this->createMock(TcpConnection::class);
        $sourceConnection->id = 1;

        /** @var TcpConnection&MockObject $targetConnection */
        $targetConnection = $this->createMock(TcpConnection::class);
        $targetConnection->id = 2;

        // 设置目标连接的send方法直接返回true
        $targetConnection->method('send')->willReturn(true);

        // 创建事件分发器
        /** @var EventDispatcherInterface&MockObject $eventDispatcher */
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        // 创建带有自定义事件分发器的管道
        $pipe = new TcpToTcpPipe($eventDispatcher);

        // 设置连接
        $pipe->setSource($sourceConnection);
        $pipe->setTarget($targetConnection);

        // 激活管道
        $pipe->pipe();

        // 测试转发功能
        $result = $pipe->forward('test data');

        // 验证转发成功
        $this->assertTrue($result);
    }

    /**
     * 测试非活动状态的管道转发失败
     */
    public function testForwardInactive(): void
    {
        // 创建源和目标TCP连接
        /** @var TcpConnection $sourceConnection */
        $sourceConnection = $this->createMock(TcpConnection::class);
        /** @var TcpConnection $targetConnection */
        $targetConnection = $this->createMock(TcpConnection::class);

        // 设置连接但不激活管道
        $this->pipe->setSource($sourceConnection);
        $this->pipe->setTarget($targetConnection);

        // 测试转发功能，应该失败
        $result = $this->pipe->forward('test data');

        // 验证转发失败
        $this->assertFalse($result);
    }
}
