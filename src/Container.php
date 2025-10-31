<?php

namespace Tourze\Workerman\ConnectionPipe;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Workerman\Connection\ConnectionInterface;

class Container
{
    private static ?self $instance = null;

    private ?LoggerInterface $logger = null;

    private ?EventDispatcherInterface $eventDispatcher = null;

    private function __construct()
    {
    }

    public static function getInstance(): self
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function getLogger(?ConnectionInterface $connection = null): ?LoggerInterface
    {
        return $this->logger;
    }

    public function getEventDispatcher(?ConnectionInterface $connection = null): ?EventDispatcherInterface
    {
        return $this->eventDispatcher;
    }

    public function setLogger(?LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    public function setEventDispatcher(?EventDispatcherInterface $eventDispatcher): void
    {
        $this->eventDispatcher = $eventDispatcher;
    }
}
