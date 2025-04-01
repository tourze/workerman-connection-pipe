<?php

namespace Tourze\Workerman\ConnectionPipe\Contracts;

use Workerman\Connection\ConnectionInterface;

/**
 * 连接管道接口
 * 定义两个不同连接之间数据转发的基本操作
 */
interface ConnectionPipeInterface
{
    /**
     * 获取源连接
     *
     * @return ConnectionInterface 源连接对象
     */
    public function getSource(): ConnectionInterface;

    /**
     * 获取目标连接
     *
     * @return ConnectionInterface 目标连接对象
     */
    public function getTarget(): ConnectionInterface;

    /**
     * 设置源连接
     *
     * @param ConnectionInterface $connection 源连接对象
     */
    public function setSource(ConnectionInterface $connection): void;

    /**
     * 设置目标连接
     *
     * @param ConnectionInterface $connection 目标连接对象
     */
    public function setTarget(ConnectionInterface $connection): void;

    /**
     * 启动管道
     * 开始监听源连接的数据并转发到目标连接
     *
     * @return self 当前对象，支持链式调用
     */
    public function pipe(): self;

    /**
     * 停止管道
     * 停止数据转发
     *
     * @return self 当前对象，支持链式调用
     */
    public function unpipe(): self;

    /**
     * 转发数据
     * 从源连接到目标连接转发特定数据
     *
     * @param string $data 要转发的数据
     * @param string $sourceAddress 源地址(UDP)
     * @param int $sourcePort 源端口(UDP)
     * @return bool 成功返回true，失败返回false
     */
    public function forward(string $data, string $sourceAddress = '', int $sourcePort = 0): bool;

    /**
     * 获取管道的协议信息
     *
     * @return array 包含源协议和目标协议的数组，例如 ['source' => 'tcp', 'target' => 'udp']
     */
    public function getProtocols(): array;

    /**
     * 检查管道是否处于活动状态
     *
     * @return bool 如果管道正在转发数据，返回true
     */
    public function isActive(): bool;

    /**
     * 获取管道唯一标识
     *
     * @return string 管道的唯一标识
     */
    public function getId(): string;
}
