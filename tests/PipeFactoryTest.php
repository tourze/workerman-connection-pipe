<?php

namespace Tourze\Workerman\ConnectionPipe\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\Workerman\ConnectionPipe\Pipe\TcpToTcpPipe;
use Tourze\Workerman\ConnectionPipe\Pipe\TcpToUdpPipe;
use Tourze\Workerman\ConnectionPipe\Pipe\UdpToTcpPipe;
use Tourze\Workerman\ConnectionPipe\Pipe\UdpToUdpPipe;
use Tourze\Workerman\ConnectionPipe\PipeFactory;
use Workerman\Connection\TcpConnection;
use Workerman\Connection\UdpConnection;

/**
 * @internal
 */
#[CoversClass(PipeFactory::class)]
final class PipeFactoryTest extends TestCase
{
    /**
     * 测试创建TCP到TCP的管道
     */
    public function testCreateTcpToTcp(): void
    {
        // 必须使用具体类 TcpConnection 的原因：
        // 1. 测试需要验证类型验证逻辑的正确性
        // 2. Workerman 的连接类型检查是基于具体类的 instanceof 判断
        // 3. 需要模拟真实场景中的连接对象行为
        $source = $this->createMock(TcpConnection::class);
        self::assertInstanceOf(TcpConnection::class, $source);
        // 必须使用具体类 TcpConnection 的原因：
        // 1. 测试需要验证类型验证逻辑的正确性
        // 2. Workerman 的连接类型检查是基于具体类的 instanceof 判断
        // 3. 需要模拟真实场景中的连接对象行为
        $target = $this->createMock(TcpConnection::class);
        self::assertInstanceOf(TcpConnection::class, $target);

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
        // 必须使用具体类 TcpConnection 的原因：
        // 1. 测试需要验证类型验证逻辑的正确性
        // 2. Workerman 的连接类型检查是基于具体类的 instanceof 判断
        // 3. 需要模拟真实场景中的连接对象行为
        $source = $this->createMock(TcpConnection::class);
        self::assertInstanceOf(TcpConnection::class, $source);
        // 必须使用具体类 UdpConnection 的原因：
        // 1. 测试需要验证类型验证逻辑的正确性
        // 2. Workerman 的连接类型检查是基于具体类的 instanceof 判断
        // 3. 需要模拟真实场景中的连接对象行为
        $target = $this->createMock(UdpConnection::class);
        self::assertInstanceOf(UdpConnection::class, $target);

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
        // 必须使用具体类 UdpConnection 的原因：
        // 1. 测试需要验证类型验证逻辑的正确性
        // 2. Workerman 的连接类型检查是基于具体类的 instanceof 判断
        // 3. 需要模拟真实场景中的连接对象行为
        $source = $this->createMock(UdpConnection::class);
        self::assertInstanceOf(UdpConnection::class, $source);
        // 必须使用具体类 TcpConnection 的原因：
        // 1. 测试需要验证类型验证逻辑的正确性
        // 2. Workerman 的连接类型检查是基于具体类的 instanceof 判断
        // 3. 需要模拟真实场景中的连接对象行为
        $target = $this->createMock(TcpConnection::class);
        self::assertInstanceOf(TcpConnection::class, $target);

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
        // 必须使用具体类 UdpConnection 的原因：
        // 1. 测试需要验证类型验证逻辑的正确性
        // 2. Workerman 的连接类型检查是基于具体类的 instanceof 判断
        // 3. 需要模拟真实场景中的连接对象行为
        $source = $this->createMock(UdpConnection::class);
        self::assertInstanceOf(UdpConnection::class, $source);
        // 必须使用具体类 UdpConnection 的原因：
        // 1. 测试需要验证类型验证逻辑的正确性
        // 2. Workerman 的连接类型检查是基于具体类的 instanceof 判断
        // 3. 需要模拟真实场景中的连接对象行为
        $target = $this->createMock(UdpConnection::class);
        self::assertInstanceOf(UdpConnection::class, $target);

        // 创建管道
        $pipe = PipeFactory::createUdpToUdp($source, $target);

        // 验证管道类型和属性
        $this->assertInstanceOf(UdpToUdpPipe::class, $pipe);
        $this->assertSame($source, $pipe->getSource());
        $this->assertSame($target, $pipe->getTarget());
    }
}
