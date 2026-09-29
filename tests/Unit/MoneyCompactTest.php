<?php

declare(strict_types=1);

use App\Support\Money;

test('amounts under $1,000 are left as ordinary formatted currency', function () {
    expect(Money::compact(58.39))->toBe('$58.39')
        ->and(Money::compact(0.0))->toBe('$0.00')
        ->and(Money::compact(999.99))->toBe('$999.99');
});

test('thousands compact to K, trimming a trailing .0', function () {
    expect(Money::compact(1000.0))->toBe('$1K')
        ->and(Money::compact(1500.0))->toBe('$1.5K')
        ->and(Money::compact(1124.08))->toBe('$1.1K')
        ->and(Money::compact(220100.0))->toBe('$220.1K');
});

test('millions compact to M', function () {
    expect(Money::compact(2_300_000.0))->toBe('$2.3M')
        ->and(Money::compact(1_000_000.0))->toBe('$1M');
});

test('negative amounts keep their sign in front of the dollar sign', function () {
    expect(Money::compact(-1500.0))->toBe('-$1.5K')
        ->and(Money::compact(-58.39))->toBe('-$58.39');
});
