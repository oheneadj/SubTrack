<?php

declare(strict_types=1);

namespace App\Enums;

enum EmailLogStatus: string
{
    case Queued = 'Queued';
    case Sent = 'Sent';
    case Failed = 'Failed';

    public function label(): string
    {
        return $this->value;
    }

    public function color(): string
    {
        return match ($this) {
            self::Queued => 'neutral',
            self::Sent => 'success',
            self::Failed => 'error',
        };
    }
}
