<?php

namespace Tourze\Workerman\ConnectionPipe\DTO;

/**
 * 数据转发上下文信息
 */
class ForwardContext
{
    /**
     * @param string|null $sourceId 源连接ID
     * @param string|null $targetId 目标连接ID
     * @param string|null $sourceAddress 源地址
     * @param int|null $sourcePort 源端口
     * @param string|null $sourceLocalAddress 源本地地址
     * @param string|null $targetAddress 目标地址
     * @param int|null $targetPort 目标端口
     * @param string|null $targetLocalAddress 目标本地地址
     * @param string|null $targetRemoteAddress 目标远程地址
     */
    public function __construct(
        public readonly ?string $sourceId = null,
        public readonly ?string $targetId = null,
        public readonly ?string $sourceAddress = null,
        public readonly ?int $sourcePort = null,
        public readonly ?string $sourceLocalAddress = null,
        public readonly ?string $targetAddress = null,
        public readonly ?int $targetPort = null,
        public readonly ?string $targetLocalAddress = null,
        public readonly ?string $targetRemoteAddress = null,
    ) {
    }

    /**
     * 转换为数组（用于日志记录等）
     */
    public function toArray(): array
    {
        return array_filter([
            'sourceId' => $this->sourceId,
            'targetId' => $this->targetId,
            'sourceAddress' => $this->sourceAddress,
            'sourcePort' => $this->sourcePort,
            'sourceLocalAddress' => $this->sourceLocalAddress,
            'targetAddress' => $this->targetAddress,
            'targetPort' => $this->targetPort,
            'targetLocalAddress' => $this->targetLocalAddress,
            'targetRemoteAddress' => $this->targetRemoteAddress,
        ], fn($value) => $value !== null);
    }
}
