<?php

namespace Tourze\Workerman\ConnectionPipe;

use Tourze\Workerman\ConnectionPipe\Pipe\TcpToTcpPipe;
use Tourze\Workerman\ConnectionPipe\Pipe\TcpToUdpPipe;
use Tourze\Workerman\ConnectionPipe\Pipe\UdpToTcpPipe;
use Tourze\Workerman\ConnectionPipe\Pipe\UdpToUdpPipe;
use Workerman\Connection\TcpConnection;
use Workerman\Connection\UdpConnection;

/**
 * 连接管道工厂
 * 用于根据连接类型创建适合的管道
 */
class PipeFactory
{
    /**
     * 创建TCP到TCP的管道
     */
    public static function createTcpToTcp(TcpConnection $source, TcpConnection $target): TcpToTcpPipe
    {
        $pipe = new TcpToTcpPipe();
        $pipe->setSource($source);
        $pipe->setTarget($target);

        return $pipe;
    }

    /**
     * 创建TCP到UDP的管道
     */
    public static function createTcpToUdp(TcpConnection $source, UdpConnection $target): TcpToUdpPipe
    {
        $pipe = new TcpToUdpPipe();
        $pipe->setSource($source);
        $pipe->setTarget($target);

        return $pipe;
    }

    /**
     * 创建UDP到TCP的管道
     */
    public static function createUdpToTcp(UdpConnection $source, TcpConnection $target): UdpToTcpPipe
    {
        $pipe = new UdpToTcpPipe();
        $pipe->setSource($source);
        $pipe->setTarget($target);

        return $pipe;
    }

    /**
     * 创建UDP到UDP的管道
     */
    public static function createUdpToUdp(UdpConnection $source, UdpConnection $target): UdpToUdpPipe
    {
        $pipe = new UdpToUdpPipe();
        $pipe->setSource($source);
        $pipe->setTarget($target);

        return $pipe;
    }
}
