<?php

declare(strict_types=1);

use App\Enums\SubscriptionRenewalType;
use Carbon\CarbonImmutable;

test('one-time monthly and annually auto-generate expiry from purchase date', function () {
    $purchase = CarbonImmutable::parse('2026-01-15');

    expect(SubscriptionRenewalType::OneTimeMonthly->expiryFrom($purchase))
        ->toEqual($purchase->addMonth());
    expect(SubscriptionRenewalType::OneTimeAnnually->expiryFrom($purchase))
        ->toEqual($purchase->addYear());
});

test('recurring monthly and annually auto-generate expiry from purchase date', function () {
    $purchase = CarbonImmutable::parse('2026-01-15');

    expect(SubscriptionRenewalType::RecurringMonthly->expiryFrom($purchase))
        ->toEqual($purchase->addMonth());
    expect(SubscriptionRenewalType::RecurringAnnually->expiryFrom($purchase))
        ->toEqual($purchase->addYear());
});

test('bare one-time has no implied duration', function () {
    expect(SubscriptionRenewalType::OneTime->expiryFrom(CarbonImmutable::now()))->toBeNull();
});

test('only recurring types report as recurring', function () {
    expect(SubscriptionRenewalType::RecurringMonthly->isRecurring())->toBeTrue();
    expect(SubscriptionRenewalType::RecurringAnnually->isRecurring())->toBeTrue();
    expect(SubscriptionRenewalType::OneTime->isRecurring())->toBeFalse();
    expect(SubscriptionRenewalType::OneTimeMonthly->isRecurring())->toBeFalse();
    expect(SubscriptionRenewalType::OneTimeAnnually->isRecurring())->toBeFalse();
});
