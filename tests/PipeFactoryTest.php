<?php

namespace Tourze\Workerman\ConnectionPipe\Tests;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tourze\Workerman\ConnectionPipe\Pipe\TcpToTcpPipe;
use Tourze\Workerman\ConnectionPipe\Pipe\TcpToUdpPipe;
use Tourze\Workerman\ConnectionPipe\Pipe\UdpToTcpPipe;
use Tourze\Workerman\ConnectionPipe\Pipe\UdpToUdpPipe;
use Tourze\Workerman\ConnectionPipe\PipeFactory;
use Workerman\Connection\TcpConnection;
use Workerman\Connection\UdpConnection;

class PipeFactoryTest extends TestCase
{
    /**
     * 测试创建TCP到TCP的管道
     */
    public function testCreateTcpToTcp(): void
    {
        // 模拟TCP连接
        /** @var TcpConnection&MockObject $source */
        $source = $this->createMock(TcpConnection::class);
        /** @var TcpConnection&MockObject $target */
        $target = $this->createMock(TcpConnection::class);

        // 创建管道
        $pipe = PipeFactory::createTcpToTcp($source, $target);

        // 验证管道类型和属性
        $this->assertInstanceOf(TcpToTcpPipe::class, $pipe);
        $this->assertSame($source, $pipe->getSource());
        $this->assertSame($target, $pipe->getTarget());
    }

    /**
     * 测试创建TCP到UDP的管道
     */
    public function testCreateTcpToUdp(): void
    {
        // 模拟连接
        /** @var TcpConnection&MockObject $source */
        $source = $this->createMock(TcpConnection::class);
        /** @var UdpConnection&MockObject $target */
        $target = $this->createMock(UdpConnection::class);

        // 创建管道
        $pipe = PipeFactory::createTcpToUdp($source, $target);

        // 验证管道类型和属性
        $this->assertInstanceOf(TcpToUdpPipe::class, $pipe);
        $this->assertSame($source, $pipe->getSource());
        $this->assertSame($target, $pipe->getTarget());
    }

    /**
     * 测试创建UDP到TCP的管道
     */
    public function testCreateUdpToTcp(): void
    {
        // 模拟连接
        /** @var UdpConnection&MockObject $source */
        $source = $this->createMock(UdpConnection::class);
        /** @var TcpConnection&MockObject $target */
        $target = $this->createMock(TcpConnection::class);

        // 创建管道
        $pipe = PipeFactory::createUdpToTcp($source, $target);

        // 验证管道类型和属性
        $this->assertInstanceOf(UdpToTcpPipe::class, $pipe);
        $this->assertSame($source, $pipe->getSource());
        $this->assertSame($target, $pipe->getTarget());
    }

    /**
     * 测试创建UDP到UDP的管道
     */
    public function testCreateUdpToUdp(): void
    {
        // 模拟连接
        /** @var UdpConnection&MockObject $source */
        $source = $this->createMock(UdpConnection::class);
        /** @var UdpConnection&MockObject $target */
        $target = $this->createMock(UdpConnection::class);

        // 创建管道
        $pipe = PipeFactory::createUdpToUdp($source, $target);

        // 验证管道类型和属性
        $this->assertInstanceOf(UdpToUdpPipe::class, $pipe);
        $this->assertSame($source, $pipe->getSource());
        $this->assertSame($target, $pipe->getTarget());
    }
}
