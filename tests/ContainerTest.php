<?php

namespace Tourze\Workerman\ConnectionPipe\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Tourze\Workerman\ConnectionPipe\Container;

/**
 * @internal
 */
#[CoversClass(Container::class)]
final class ContainerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // 在每个测试前重置容器状态
        Container::getInstance()->setLogger(null);
        Container::getInstance()->setEventDispatcher(null);
    }

    public function testSetGetLogger(): void
    {
        // 创建一个模拟的Logger对象
        $logger = $this->createMock(LoggerInterface::class);
        self::assertInstanceOf(LoggerInterface::class, $logger);

        // 设置Logger
        Container::getInstance()->setLogger($logger);

        // 验证获取到的Logger是同一个实例
        $this->assertSame($logger, Container::getInstance()->getLogger());
    }

    public function testSetGetEventDispatcher(): void
    {
        // 创建一个模拟的EventDispatcher对象
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        self::assertInstanceOf(EventDispatcherInterface::class, $eventDispatcher);

        // 设置EventDispatcher
        Container::getInstance()->setEventDispatcher($eventDispatcher);

        // 验证获取到的EventDispatcher是同一个实例
        $this->assertSame($eventDispatcher, Container::getInstance()->getEventDispatcher());
    }

    public function testDefaultValues(): void
    {
        // 确保默认值为null
        Container::getInstance()->setLogger(null);
        Container::getInstance()->setEventDispatcher(null);

        $this->assertNull(Container::getInstance()->getLogger());
        $this->assertNull(Container::getInstance()->getEventDispatcher());
    }
}
