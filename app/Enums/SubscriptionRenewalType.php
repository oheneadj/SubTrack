<?php

declare(strict_types=1);

namespace App\Enums;

use Carbon\CarbonImmutable;

/**
 * How a subscription is billed and whether it auto-renews.
 *
 * The two "one-off with a term" cases (OneTimeMonthly/OneTimeAnnually) and the
 * two recurring cases carry an implied duration used to auto-generate the
 * expiry date from the purchase date. Bare OneTime has no implied duration —
 * it's a one-off purchase with no fixed term, so its expiry date is always
 * entered manually.
 */
enum SubscriptionRenewalType: string
{
    case OneTime = 'OneTime';
    case OneTimeMonthly = 'OneTimeMonthly';
    case OneTimeAnnually = 'OneTimeAnnually';
    case RecurringMonthly = 'RecurringMonthly';
    case RecurringAnnually = 'RecurringAnnually';

    /** Human-readable label shown in selects and badges. */
    public function label(): string
    {
        return match ($this) {
            self::OneTime => 'One-time',
            self::OneTimeMonthly => 'One-time (Monthly)',
            self::OneTimeAnnually => 'One-time (Annually)',
            self::RecurringMonthly => 'Recurring (Monthly)',
            self::RecurringAnnually => 'Recurring (Annually)',
        };
    }

    /**
     * Whether this subscription is expected to keep renewing.
     * Drives whether the "Process Renewal" action is offered and whether it
     * shows up in the renewal tracker at all.
     */
    public function isRecurring(): bool
    {
        return match ($this) {
            self::RecurringMonthly, self::RecurringAnnually => true,
            default => false,
        };
    }

    /**
     * The billing cycle length for renewal-date math, or null when this type
     * has no fixed term (bare OneTime).
     */
    public function cycleMonths(): ?int
    {
        return match ($this) {
            self::OneTimeMonthly, self::RecurringMonthly => 1,
            self::OneTimeAnnually, self::RecurringAnnually => 12,
            self::OneTime => null,
        };
    }

    /**
     * Auto-generate the expiry date from the purchase date for types that
     * carry an implied duration. Returns null for bare OneTime, meaning:
     * leave whatever expiry date was entered manually alone.
     */
    public function expiryFrom(CarbonImmutable $purchaseDate): ?CarbonImmutable
    {
        $months = $this->cycleMonths();

        return $months === null ? null : $purchaseDate->addMonths($months);
    }
}
