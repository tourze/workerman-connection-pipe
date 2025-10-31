<?php

namespace Tourze\Workerman\ConnectionPipe\Model;

use Tourze\Workerman\ConnectionPipe\Enum\ProtocolFamily;

/**
 * 地址/端口
 */
class Address implements \Stringable
{
    private ProtocolFamily $protocol;

    public function getProtocol(): ProtocolFamily
    {
        return $this->protocol;
    }

    public function setProtocol(ProtocolFamily $protocol): void
    {
        $this->protocol = $protocol;
    }

    private string $host;

    public function getHost(): string
    {
        return $this->host;
    }

    public function setHost(string $host): void
    {
        $this->host = $host;
    }

    private int $port;

    public function getPort(): int
    {
        return $this->port;
    }

    public function setPort(int $port): void
    {
        $this->port = $port;
    }

    public static function create(string $host, int $port, ProtocolFamily $protocol): Address
    {
        $address = new self();
        $address->setHost($host);
        $address->setPort($port);
        $address->setProtocol($protocol);

        return $address;
    }

    public function __toString(): string
    {
        return "{$this->getHost()}:{$this->getPort()}";
    }
}
