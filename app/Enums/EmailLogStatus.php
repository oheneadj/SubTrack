<?php

declare(strict_types=1);

namespace App\Enums;

enum EmailLogStatus: string
{
    case Queued = 'Queued';
    case Sent = 'Sent';
    case Delivered = 'Delivered';
    case Bounced = 'Bounced';
    case Blocked = 'Blocked';
    case Complained = 'Complained';
    case Failed = 'Failed';

    public function label(): string
    {
        return $this->value;
    }

    public function color(): string
    {
        return match ($this) {
            self::Queued => 'neutral',
            self::Sent => 'info',
            self::Delivered => 'success',
            self::Bounced, self::Blocked, self::Complained, self::Failed => 'error',
        };
    }
}
