<?php

namespace Tourze\Workerman\ConnectionPipe;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * 如果项目中的Container类没有静态方法setLogger和setEventDispatcher，
 * 这个文件提供了临时解决方案
 */
if (!method_exists(Container::class, 'setLogger')) {
    class_alias(ContainerHelper::class, Container::class);
}

/**
 * 容器辅助类
 * 用于静态持有日志和事件分发器实例
 */
class ContainerHelper
{
    /**
     * 日志实例
     */
    private static ?LoggerInterface $logger = null;

    /**
     * 事件分发器实例
     */
    private static ?EventDispatcherInterface $eventDispatcher = null;

    /**
     * 设置日志实例
     */
    public static function setLogger(LoggerInterface $logger): void
    {
        self::$logger = $logger;
    }

    /**
     * 获取日志实例
     */
    public static function getLogger(): ?LoggerInterface
    {
        return self::$logger;
    }

    /**
     * 设置事件分发器实例
     */
    public static function setEventDispatcher(EventDispatcherInterface $dispatcher): void
    {
        self::$eventDispatcher = $dispatcher;
    }

    /**
     * 获取事件分发器实例
     */
    public static function getEventDispatcher(): ?EventDispatcherInterface
    {
        return self::$eventDispatcher;
    }
}
