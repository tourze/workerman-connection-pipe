<?php

namespace Tourze\Workerman\ConnectionPipe\Tests\Model;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\Workerman\ConnectionPipe\Enum\ProtocolFamily;
use Tourze\Workerman\ConnectionPipe\Model\Address;

/**
 * @internal
 */
#[CoversClass(Address::class)]
final class AddressTest extends TestCase
{
    public function testGetSetProtocol(): void
    {
        $address = new Address();
        $protocol = ProtocolFamily::TCP;

        $address->setProtocol($protocol);
        $this->assertSame($protocol, $address->getProtocol());
    }

    public function testGetSetHost(): void
    {
        $address = new Address();
        $host = '192.168.1.1';

        $address->setHost($host);
        $this->assertSame($host, $address->getHost());
    }

    public function testGetSetPort(): void
    {
        $address = new Address();
        $port = 8080;

        $address->setPort($port);
        $this->assertSame($port, $address->getPort());
    }

    public function testCreate(): void
    {
        $host = '192.168.1.1';
        $port = 8080;
        $protocol = ProtocolFamily::TCP;

        $address = Address::create($host, $port, $protocol);

        $this->assertInstanceOf(Address::class, $address);
        $this->assertSame($host, $address->getHost());
        $this->assertSame($port, $address->getPort());
        $this->assertSame($protocol, $address->getProtocol());
    }

    public function testToString(): void
    {
        $host = '192.168.1.1';
        $port = 8080;

        $address = new Address();
        $address->setHost($host);
        $address->setPort($port);

        $this->assertSame('192.168.1.1:8080', (string) $address);
    }

    public function testImplementsStringable(): void
    {
        $address = new Address();
        $this->assertInstanceOf(\Stringable::class, $address);
    }
}
