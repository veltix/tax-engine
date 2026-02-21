<?php

declare(strict_types=1);

use Veltix\TaxEngine\Enums\CustomerType;

it('has all expected cases', function () {
    $cases = CustomerType::cases();

    expect($cases)->toHaveCount(3)
        ->and($cases)->toContain(CustomerType::B2B)
        ->and($cases)->toContain(CustomerType::B2C)
        ->and($cases)->toContain(CustomerType::Government);
});

it('has correct backed values', function () {
    expect(CustomerType::B2B->value)->toBe('b2b')
        ->and(CustomerType::B2C->value)->toBe('b2c')
        ->and(CustomerType::Government->value)->toBe('gov');
});

it('can be created from value', function () {
    expect(CustomerType::from('b2b'))->toBe(CustomerType::B2B)
        ->and(CustomerType::from('b2c'))->toBe(CustomerType::B2C)
        ->and(CustomerType::from('gov'))->toBe(CustomerType::Government);
});

it('returns null from tryFrom for invalid value', function () {
    expect(CustomerType::tryFrom('invalid'))->toBeNull();
});
