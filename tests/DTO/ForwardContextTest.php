<?php

namespace Tourze\Workerman\ConnectionPipe\Tests\DTO;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\Workerman\ConnectionPipe\DTO\ForwardContext;

/**
 * @internal
 */
#[CoversClass(ForwardContext::class)]
final class ForwardContextTest extends TestCase
{
    /**
     * 测试构造函数和属性访问
     */
    public function testConstructorAndProperties(): void
    {
        $context = new ForwardContext(
            sourceId: 'source123',
            targetId: 'target456',
            sourceAddress: '192.168.1.1',
            sourcePort: 8080,
            sourceLocalAddress: '127.0.0.1:8080',
            targetAddress: '192.168.1.2',
            targetPort: 9090,
            targetLocalAddress: '127.0.0.1:9090',
            targetRemoteAddress: '192.168.1.3:9999'
        );

        $this->assertEquals('source123', $context->sourceId);
        $this->assertEquals('target456', $context->targetId);
        $this->assertEquals('192.168.1.1', $context->sourceAddress);
        $this->assertEquals(8080, $context->sourcePort);
        $this->assertEquals('127.0.0.1:8080', $context->sourceLocalAddress);
        $this->assertEquals('192.168.1.2', $context->targetAddress);
        $this->assertEquals(9090, $context->targetPort);
        $this->assertEquals('127.0.0.1:9090', $context->targetLocalAddress);
        $this->assertEquals('192.168.1.3:9999', $context->targetRemoteAddress);
    }

    /**
     * 测试默认值
     */
    public function testDefaultValues(): void
    {
        $context = new ForwardContext();

        $this->assertNull($context->sourceId);
        $this->assertNull($context->targetId);
        $this->assertNull($context->sourceAddress);
        $this->assertNull($context->sourcePort);
        $this->assertNull($context->sourceLocalAddress);
        $this->assertNull($context->targetAddress);
        $this->assertNull($context->targetPort);
        $this->assertNull($context->targetLocalAddress);
        $this->assertNull($context->targetRemoteAddress);
    }

    /**
     * 测试 toArray 方法 - 所有字段都有值
     */
    public function testToArrayWithAllFields(): void
    {
        $context = new ForwardContext(
            sourceId: 'source123',
            targetId: 'target456',
            sourceAddress: '192.168.1.1',
            sourcePort: 8080,
            sourceLocalAddress: '127.0.0.1:8080',
            targetAddress: '192.168.1.2',
            targetPort: 9090,
            targetLocalAddress: '127.0.0.1:9090',
            targetRemoteAddress: '192.168.1.3:9999'
        );

        $expected = [
            'sourceId' => 'source123',
            'targetId' => 'target456',
            'sourceAddress' => '192.168.1.1',
            'sourcePort' => 8080,
            'sourceLocalAddress' => '127.0.0.1:8080',
            'targetAddress' => '192.168.1.2',
            'targetPort' => 9090,
            'targetLocalAddress' => '127.0.0.1:9090',
            'targetRemoteAddress' => '192.168.1.3:9999',
        ];

        $this->assertEquals($expected, $context->toArray());
    }

    /**
     * 测试 toArray 方法 - 只有部分字段有值
     */
    public function testToArrayWithPartialFields(): void
    {
        $context = new ForwardContext(
            sourceAddress: '192.168.1.1',
            sourcePort: 8080,
            targetAddress: '192.168.1.2'
        );

        $expected = [
            'sourceAddress' => '192.168.1.1',
            'sourcePort' => 8080,
            'targetAddress' => '192.168.1.2',
        ];

        $this->assertEquals($expected, $context->toArray());
    }

    /**
     * 测试 toArray 方法 - 空对象
     */
    public function testToArrayWithEmptyContext(): void
    {
        $context = new ForwardContext();

        $this->assertEquals([], $context->toArray());
    }

    /**
     * 测试只读属性
     */
    public function testReadonlyProperties(): void
    {
        $context = new ForwardContext(sourceId: 'test123');

        // 通过反射检查属性是否为只读
        $reflection = new \ReflectionClass($context);
        $property = $reflection->getProperty('sourceId');

        $this->assertTrue($property->isReadOnly());
    }
}
