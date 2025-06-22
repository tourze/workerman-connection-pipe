<?php

namespace Tourze\Workerman\ConnectionPipe\Tests\Pipe;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Tourze\Workerman\ConnectionPipe\Pipe\TcpToUdpPipe;
use Workerman\Connection\TcpConnection;
use Workerman\Connection\UdpConnection;

class TcpToUdpPipeTest extends TestCase
{
    private TcpToUdpPipe $pipe;

    /**
     * 测试设置源连接的类型检查
     */
    public function testSetSourceTypeCheck(): void
    {
        // 模拟一个TCP连接
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
        // 使用UDP连接而不是TCP连接
        /** @var UdpConnection&MockObject $udpConnection */
        $udpConnection = $this->createMock(UdpConnection::class);

        // 期望抛出异常
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Source connection must be an instance of Workerman\Connection\TcpConnection');

        // 尝试设置UDP连接作为源
        $this->pipe->setSource($udpConnection);
    }

    /**
     * 测试设置目标连接的类型检查
     */
    public function testSetTargetTypeCheck(): void
    {
        // 模拟一个UDP连接
        $udpConnection = $this->createMock(UdpConnection::class);

        // 设置目标连接（应该不会抛出异常）
        $this->pipe->setTarget($udpConnection);

        // 验证连接已设置
        $this->assertSame($udpConnection, $this->pipe->getTarget());
    }

    /**
     * 测试设置非UDP类型的目标连接时抛出异常
     */
    public function testSetTargetInvalidType(): void
    {
        // 使用TCP连接而不是UDP连接
        /** @var TcpConnection&MockObject $tcpConnection */
        $tcpConnection = $this->createMock(TcpConnection::class);

        // 期望抛出异常
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Target connection must be an instance of Workerman\Connection\UdpConnection');

        // 尝试设置TCP连接作为目标
        $this->pipe->setTarget($tcpConnection);
    }

    /**
     * 测试管道的转发功能
     */
    public function testForward(): void
    {
        // 创建源TCP连接和目标UDP连接
        /** @var TcpConnection&MockObject $sourceConnection */
        $sourceConnection = $this->createMock(TcpConnection::class);

        /** @var UdpConnection&MockObject $targetConnection */
        $targetConnection = $this->createMock(UdpConnection::class);

        // 设置目标连接的方法
        $targetConnection->method('send')->willReturn(true);
        $targetConnection->method('getLocalAddress')->willReturn('127.0.0.1:9001');
        $targetConnection->method('getRemoteIp')->willReturn('127.0.0.1');
        $targetConnection->method('getRemotePort')->willReturn(9002);

        // 创建事件分发器
        /** @var EventDispatcherInterface&MockObject $eventDispatcher */
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        // 创建带有自定义事件分发器的管道
        $pipe = new TcpToUdpPipe($eventDispatcher);

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
        // 创建源和目标连接
        $sourceConnection = $this->createMock(TcpConnection::class);
        $targetConnection = $this->createMock(UdpConnection::class);

        // 设置连接但不激活管道
        $this->pipe->setSource($sourceConnection);
        $this->pipe->setTarget($targetConnection);

        // 测试转发功能，应该失败
        $result = $this->pipe->forward('test data');

        // 验证转发失败
        $this->assertFalse($result);
    }

    protected function setUp(): void
    {
        parent::setUp();

        // 创建事件分发器和日志记录器模拟对象
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        // 创建管道实例
        $this->pipe = new TcpToUdpPipe($eventDispatcher, $logger);
    }
}