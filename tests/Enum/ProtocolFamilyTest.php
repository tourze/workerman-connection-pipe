<?php

namespace Tourze\Workerman\ConnectionPipe\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;
use Tourze\Workerman\ConnectionPipe\Enum\ProtocolFamily;

/**
 * @internal
 */
#[CoversClass(ProtocolFamily::class)]
final class ProtocolFamilyTest extends AbstractEnumTestCase
{
    public function testEnumValues(): void
    {
        // 测试枚举值存在性
        $this->assertTrue(enum_exists(ProtocolFamily::class));

        // 测试各个枚举值及其对应的字符串值
        $this->assertSame('tcp', ProtocolFamily::TCP->value);
        $this->assertSame('udp', ProtocolFamily::UDP->value);
    }

    public function testEnumCases(): void
    {
        // 测试可以通过from方法正确获取对应的枚举实例
        $this->assertSame(ProtocolFamily::TCP, ProtocolFamily::from('tcp'));
        $this->assertSame(ProtocolFamily::UDP, ProtocolFamily::from('udp'));
    }

    public function testInvalidEnum(): void
    {
        // 测试无效的枚举值会抛出异常
        $this->expectException(\ValueError::class);
        ProtocolFamily::from('invalid_protocol');
    }

    public function testTryFrom(): void
    {
        // 测试tryFrom方法正确处理有效值和无效值
        $this->assertSame(ProtocolFamily::TCP, ProtocolFamily::tryFrom('tcp'));
        $this->assertSame(ProtocolFamily::UDP, ProtocolFamily::tryFrom('udp'));
        $this->assertNull(ProtocolFamily::tryFrom('invalid_protocol'));
    }

    public function testToArray(): void
    {
        // 测试toArray方法返回包含value和label的数组
        $tcpArray = ProtocolFamily::TCP->toArray();
        $this->assertIsArray($tcpArray);
        $this->assertArrayHasKey('value', $tcpArray);
        $this->assertArrayHasKey('label', $tcpArray);
        $this->assertSame('tcp', $tcpArray['value']);
        $this->assertSame('TCP', $tcpArray['label']);

        $udpArray = ProtocolFamily::UDP->toArray();
        $this->assertIsArray($udpArray);
        $this->assertArrayHasKey('value', $udpArray);
        $this->assertArrayHasKey('label', $udpArray);
        $this->assertSame('udp', $udpArray['value']);
        $this->assertSame('UDP', $udpArray['label']);
    }
}
