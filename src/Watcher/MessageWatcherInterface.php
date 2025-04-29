<?php

namespace Tourze\Workerman\ConnectionPipe\Watcher;

use Workerman\Connection\ConnectionInterface;

interface MessageWatcherInterface
{
    public function __invoke(mixed $data, ConnectionInterface $source, ConnectionInterface $target, callable $resolver): void;
}
