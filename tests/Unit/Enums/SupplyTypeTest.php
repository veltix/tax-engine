<?php

declare(strict_types=1);

use Veltix\TaxEngine\Enums\SupplyType;

it('has all expected cases', function () {
    $cases = SupplyType::cases();

    expect($cases)->toHaveCount(5)
        ->and($cases)->toContain(SupplyType::Goods)
        ->and($cases)->toContain(SupplyType::Services)
        ->and($cases)->toContain(SupplyType::DigitalServices)
        ->and($cases)->toContain(SupplyType::Telecommunications)
        ->and($cases)->toContain(SupplyType::Broadcasting);
});

it('has correct backed values', function () {
    expect(SupplyType::Goods->value)->toBe('goods')
        ->and(SupplyType::Services->value)->toBe('services')
        ->and(SupplyType::DigitalServices->value)->toBe('digital_services')
        ->and(SupplyType::Telecommunications->value)->toBe('telecom')
        ->and(SupplyType::Broadcasting->value)->toBe('broadcasting');
});

it('can be created from value', function () {
    expect(SupplyType::from('goods'))->toBe(SupplyType::Goods)
        ->and(SupplyType::from('digital_services'))->toBe(SupplyType::DigitalServices);
});

it('returns null from tryFrom for invalid value', function () {
    expect(SupplyType::tryFrom('invalid'))->toBeNull();
});
