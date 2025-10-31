<?php

namespace Tourze\Workerman\ConnectionPipe\Tests\Event;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitSymfonyUnitTest\AbstractEventTestCase;
use Tourze\Workerman\ConnectionPipe\Contracts\ConnectionPipeInterface;
use Tourze\Workerman\ConnectionPipe\Event\DataForwardedEvent;

/**
 * @internal
 */
#[CoversClass(DataForwardedEvent::class)]
final class DataForwardedEventTest extends AbstractEventTestCase
{
    /**
     * 测试事件构造和属性访问器
     */
    public function testEventConstruction(): void
    {
        // 模拟管道接口
        $pipe = $this->createMock(ConnectionPipeInterface::class);
        self::assertInstanceOf(ConnectionPipeInterface::class, $pipe);

        // 测试数据和元数据
        $data = 'test data';
        $sourceProtocol = 'TCP';
        $targetProtocol = 'UDP';
        $metadata = [
            'sourceAddress' => '127.0.0.1',
            'sourcePort' => 8080,
            'targetAddress' => '192.168.1.1',
        ];

        // 创建事件对象
        $event = new DataForwardedEvent($pipe, $data, $sourceProtocol, $targetProtocol, $metadata);

        // 测试各个getter方法
        $this->assertSame($pipe, $event->getPipe());
        $this->assertSame($data, $event->getData());
        $this->assertSame($sourceProtocol, $event->getSourceProtocol());
        $this->assertSame($targetProtocol, $event->getTargetProtocol());
        $this->assertSame($metadata, $event->getMetadata());
    }

    /**
     * 测试默认元数据
     */
    public function testDefaultMetadata(): void
    {
        // 模拟管道接口
        $pipe = $this->createMock(ConnectionPipeInterface::class);
        self::assertInstanceOf(ConnectionPipeInterface::class, $pipe);

        // 创建不带元数据的事件对象
        $event = new DataForwardedEvent($pipe, 'test data', 'TCP', 'TCP');

        // 测试默认元数据是空数组
        $this->assertSame([], $event->getMetadata());
    }
}
