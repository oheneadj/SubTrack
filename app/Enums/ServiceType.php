<?php

namespace App\Enums;

enum ServiceType: string
{
    case Domain      = 'Domain';
    case Hosting     = 'Hosting';
    case SSL         = 'SSL';
    case Maintenance = 'Maintenance';
    case Other       = 'Other';

    public function label(): string
    {
        return $this->value;
    }

    public function icon(): string
    {
        return match($this) {
            self::Domain      => 'world',
            self::Hosting     => 'server',
            self::SSL         => 'lock',
            self::Maintenance => 'tools',
            self::Other       => 'box',
        };
    }
}
