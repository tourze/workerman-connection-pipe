<?php

namespace Tourze\Workerman\ConnectionPipe\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Tourze\Workerman\ConnectionPipe\Container;

class ContainerTest extends TestCase
{
    protected function tearDown(): void
    {
        Container::setLogger(null);
        Container::setEventDispatcher(null);
    }

    public function testSetGetLogger(): void
    {
        // 创建一个模拟的Logger对象
        /** @var LoggerInterface $logger */
        $logger = $this->createMock(LoggerInterface::class);

        // 设置Logger
        Container::setLogger($logger);

        // 验证获取到的Logger是同一个实例
        $this->assertSame($logger, Container::getLogger());
    }

    public function testSetGetEventDispatcher(): void
    {
        // 创建一个模拟的EventDispatcher对象
        /** @var EventDispatcherInterface $eventDispatcher */
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        // 设置EventDispatcher
        Container::setEventDispatcher($eventDispatcher);

        // 验证获取到的EventDispatcher是同一个实例
        $this->assertSame($eventDispatcher, Container::getEventDispatcher());
    }

    public function testDefaultValues(): void
    {
        // 确保默认值为null
        Container::setLogger(null);
        Container::setEventDispatcher(null);

        $this->assertNull(Container::getLogger());
        $this->assertNull(Container::getEventDispatcher());
    }
}
