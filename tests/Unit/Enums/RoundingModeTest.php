<?php

declare(strict_types=1);

use Veltix\TaxEngine\Enums\RoundingMode;

it('has all expected cases', function () {
    $cases = RoundingMode::cases();

    expect($cases)->toHaveCount(3)
        ->and($cases)->toContain(RoundingMode::HalfUp)
        ->and($cases)->toContain(RoundingMode::HalfDown)
        ->and($cases)->toContain(RoundingMode::HalfEven);
});

it('has correct backed values', function () {
    expect(RoundingMode::HalfUp->value)->toBe('half_up')
        ->and(RoundingMode::HalfDown->value)->toBe('half_down')
        ->and(RoundingMode::HalfEven->value)->toBe('half_even');
});

it('can be created from value', function () {
    expect(RoundingMode::from('half_up'))->toBe(RoundingMode::HalfUp)
        ->and(RoundingMode::from('half_even'))->toBe(RoundingMode::HalfEven);
});

it('returns null from tryFrom for invalid value', function () {
    expect(RoundingMode::tryFrom('invalid'))->toBeNull();
});
