<?php

namespace Tourze\Workerman\ConnectionPipe\Enum;

enum ProtocolFamily: string
{
    case TCP = 'tcp';
    case UDP = 'udp';
}
