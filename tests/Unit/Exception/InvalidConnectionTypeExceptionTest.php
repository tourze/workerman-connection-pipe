<?php

namespace Tourze\Workerman\ConnectionPipe\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use Tourze\Workerman\ConnectionPipe\Exception\InvalidConnectionTypeException;

/**
 * @covers \Tourze\Workerman\ConnectionPipe\Exception\InvalidConnectionTypeException
 */
class InvalidConnectionTypeExceptionTest extends TestCase
{
    public function testCreate(): void
    {
        $connectionName = 'Source';
        $expectedClass = 'TcpConnection';
        
        $exception = InvalidConnectionTypeException::create($connectionName, $expectedClass);
        
        $this->assertInstanceOf(InvalidConnectionTypeException::class, $exception);
        $this->assertInstanceOf(\InvalidArgumentException::class, $exception);
        $this->assertSame(
            'Source connection must be an instance of TcpConnection',
            $exception->getMessage()
        );
    }

    public function testExceptionIsThrowable(): void
    {
        $this->expectException(InvalidConnectionTypeException::class);
        $this->expectExceptionMessage('Test connection must be an instance of TestClass');
        
        throw InvalidConnectionTypeException::create('Test', 'TestClass');
    }
}