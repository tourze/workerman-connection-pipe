<?php

namespace Tourze\Workerman\ConnectionPipe;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Workerman\Connection\ConnectionInterface;

class Container
{
    public static ?LoggerInterface $logger = null;

    public static ?EventDispatcherInterface $eventDispatcher = null;

    public static function getLogger(?ConnectionInterface $connection = null): ?LoggerInterface
    {
        return static::$logger;
    }

    public static function getEventDispatcher(?ConnectionInterface $connection = null): ?EventDispatcherInterface
    {
        return static::$eventDispatcher;
    }

    public static function setLogger(?LoggerInterface $logger): void
    {
        self::$logger = $logger;
    }

    public static function setEventDispatcher(?EventDispatcherInterface $eventDispatcher): void
    {
        self::$eventDispatcher = $eventDispatcher;
    }
}
