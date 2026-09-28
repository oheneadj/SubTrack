<?php

declare(strict_types=1);

namespace App\Enums;

enum ServiceType: string
{
    case Domain = 'Domain';
    case Hosting = 'Hosting';
    case SSL = 'SSL';
    case Maintenance = 'Maintenance';
    case Theme = 'Theme';
    case PageBuilder = 'Page Builder';
    case AITool = 'AI Tool';
    case Plugin = 'Plugin';
    case MailBox = 'Mail Box';
    case Other = 'Other';

    public function label(): string
    {
        return $this->value;
    }

    public function icon(): string
    {
        return match ($this) {
            self::Domain => 'world',
            self::Hosting => 'server',
            self::SSL => 'lock',
            self::Maintenance => 'tools',
            self::Theme => 'photo',
            self::PageBuilder => 'layout-dashboard',
            self::AITool => 'sparkles',
            self::Plugin => 'puzzle',
            self::MailBox => 'mail',
            self::Other => 'box',
        };
    }
}
