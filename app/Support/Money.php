<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Compact display formatting for dollar amounts already in major units
 * (i.e. already converted from cents) — for space-constrained UI like
 * dashboard stat cards, where a long precise figure such as $1,124.08
 * doesn't leave room for its icon. Never use this for money that needs
 * to reconcile to the cent (invoices, receipts, exact totals) — only for
 * compact display where some precision loss is acceptable.
 */
final class Money
{
    /** $1,124.08 -> "$1.1K", $1,500 -> "$1.5K", $2,300,000 -> "$2.3M", anything under $1,000 is left as-is. */
    public static function compact(float $amount): string
    {
        $sign = $amount < 0 ? '-' : '';
        $abs = abs($amount);

        if ($abs >= 1_000_000) {
            return $sign.'$'.self::trimTrailingZero($abs / 1_000_000).'M';
        }

        if ($abs >= 1_000) {
            return $sign.'$'.self::trimTrailingZero($abs / 1_000).'K';
        }

        return $sign.'$'.number_format($abs, 2);
    }

    private static function trimTrailingZero(float $value): string
    {
        return rtrim(rtrim(number_format($value, 1), '0'), '.');
    }
}
