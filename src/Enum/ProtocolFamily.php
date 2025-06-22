<?php

namespace Tourze\Workerman\ConnectionPipe\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

enum ProtocolFamily: string implements Itemable, Labelable, Selectable
{
    use ItemTrait;
    use SelectTrait;

    case TCP = 'tcp';
    case UDP = 'udp';

    public function getLabel(): string
    {
        return match ($this) {
            self::TCP => 'TCP',
            self::UDP => 'UDP',
        };
    }
}
