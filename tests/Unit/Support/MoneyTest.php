<?php

declare(strict_types=1);

use Veltix\TaxEngine\Enums\RoundingMode;
use Veltix\TaxEngine\Support\Money;

it('creates from cents', function () {
    $money = Money::fromCents(1999);

    expect($money->amount)->toBe(1999)
        ->and($money->currency)->toBe('EUR')
        ->and($money->precision)->toBe(2);
});

it('creates from decimal', function () {
    $money = Money::fromDecimal('19.99');

    expect($money->amount)->toBe(1999)
        ->and($money->currency)->toBe('EUR');
});

it('creates zero', function () {
    $money = Money::zero();

    expect($money->amount)->toBe(0)
        ->and($money->isZero())->toBeTrue();
});

it('roundtrips fromDecimal through toDecimalString', function (string $value) {
    expect(Money::fromDecimal($value)->toDecimalString())->toBe($value);
})->with(['19.99', '0.01', '100.00', '0.00']);

it('adds money', function () {
    $a = Money::fromCents(1000);
    $b = Money::fromCents(500);
    $result = $a->add($b);

    expect($result->amount)->toBe(1500)
        ->and($result)->not->toBe($a);
});

it('subtracts money', function () {
    $result = Money::fromCents(1000)->subtract(Money::fromCents(300));

    expect($result->amount)->toBe(700);
});

it('multiplies', function () {
    $result = Money::fromCents(1000)->multiply('2.5');

    expect($result->amount)->toBe(2500);
});

it('allocates tax', function () {
    $tax = Money::fromCents(10000)->allocateTax('21.00');

    expect($tax->amount)->toBe(2100);
});

it('allocates tax with rounding', function () {
    // 1001 * 0.21 = 210.21 → rounds to 210
    $tax = Money::fromCents(1001)->allocateTax('21.00');

    expect($tax->amount)->toBe(210);
});

it('allocates tax with half down rounding', function () {
    $tax = Money::fromCents(1000)->allocateTax('15.00', RoundingMode::HalfDown);

    expect($tax->amount)->toBe(150);
});

it('rounds half up at boundary on multiply', function () {
    // 3 * 0.5 = 1.5 → half_up rounds to 2
    $result = Money::fromCents(3)->multiply('0.5', RoundingMode::HalfUp);

    expect($result->amount)->toBe(2);
});

it('rounds half down at boundary on multiply', function () {
    // 3 * 0.5 = 1.5 → half_down rounds to 1
    $result = Money::fromCents(3)->multiply('0.5', RoundingMode::HalfDown);

    expect($result->amount)->toBe(1);
});

it('rounds half even at boundary on multiply', function () {
    // 3 * 0.5 = 1.5 → half_even rounds to 2 (nearest even)
    $result = Money::fromCents(3)->multiply('0.5', RoundingMode::HalfEven);
    expect($result->amount)->toBe(2);

    // 4 * 0.5 = 2.0 → stays at 2
    $result2 = Money::fromCents(4)->multiply('0.5', RoundingMode::HalfEven);
    expect($result2->amount)->toBe(2);
});

it('throws on currency mismatch for add', function () {
    Money::fromCents(100, 'EUR')->add(Money::fromCents(100, 'USD'));
})->throws(InvalidArgumentException::class, 'Currency mismatch');

it('throws on currency mismatch for subtract', function () {
    Money::fromCents(100, 'EUR')->subtract(Money::fromCents(50, 'GBP'));
})->throws(InvalidArgumentException::class);

it('throws on currency mismatch for greaterThan', function () {
    Money::fromCents(100, 'EUR')->greaterThan(Money::fromCents(50, 'USD'));
})->throws(InvalidArgumentException::class);

it('detects positive negative and zero', function () {
    expect(Money::fromCents(100)->isPositive())->toBeTrue()
        ->and(Money::fromCents(100)->isNegative())->toBeFalse()
        ->and(Money::fromCents(100)->isZero())->toBeFalse()
        ->and(Money::fromCents(-100)->isNegative())->toBeTrue()
        ->and(Money::fromCents(-100)->isPositive())->toBeFalse()
        ->and(Money::fromCents(0)->isZero())->toBeTrue();
});

it('compares equality', function () {
    $a = Money::fromCents(100);
    $b = Money::fromCents(100);
    $c = Money::fromCents(200);

    expect($a->equals($b))->toBeTrue()
        ->and($a->equals($c))->toBeFalse();
});

it('compares greater than', function () {
    $a = Money::fromCents(200);
    $b = Money::fromCents(100);

    expect($a->greaterThan($b))->toBeTrue()
        ->and($b->greaterThan($a))->toBeFalse();
});

it('formats toDecimalString correctly', function (int $cents, string $expected) {
    expect(Money::fromCents($cents)->toDecimalString())->toBe($expected);
})->with([
    [1000, '10.00'],
    [1, '0.01'],
    [0, '0.00'],
    [9999, '99.99'],
]);

it('includes all fields in toArray', function () {
    $array = Money::fromCents(1999, 'EUR')->toArray();

    expect($array)->toBe([
        'amount' => 1999,
        'currency' => 'EUR',
        'precision' => 2,
        'decimal' => '19.99',
    ]);
});

it('is immutable', function () {
    $original = Money::fromCents(1000);
    $added = $original->add(Money::fromCents(500));

    expect($original->amount)->toBe(1000)
        ->and($added->amount)->toBe(1500)
        ->and($added)->not->toBe($original);
});

it('rounds fromDecimal correctly instead of truncating', function (string $decimal, int $expected) {
    expect(Money::fromDecimal($decimal)->amount)->toBe($expected);
})->with([
    ['10.999', 1100],
    ['10.995', 1100],
    ['10.994', 1099],
    ['0.005', 1],
    ['0.004', 0],
]);

it('handles negative fromDecimal with rounding', function () {
    expect(Money::fromDecimal('-10.999')->amount)->toBe(-1100);
});

it('handles large amounts in fromDecimal without overflow', function () {
    $money = Money::fromDecimal('99999999.99');
    expect($money->amount)->toBe(9999999999);
});

it('handles negative cents arithmetic', function () {
    $a = Money::fromCents(-1000);
    $b = Money::fromCents(500);

    expect($a->add($b)->amount)->toBe(-500)
        ->and($a->subtract($b)->amount)->toBe(-1500);
});

it('allocates zero tax correctly', function () {
    $tax = Money::fromCents(10000)->allocateTax('0.00');
    expect($tax->amount)->toBe(0);
});
