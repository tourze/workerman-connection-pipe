<?php

namespace Tourze\Workerman\ConnectionPipe\Exception;

class InvalidConnectionTypeException extends \InvalidArgumentException
{
    public static function create(string $connectionName, string $expectedClass): self
    {
        return new self(
            sprintf('%s connection must be an instance of %s', $connectionName, $expectedClass)
        );
    }
}
