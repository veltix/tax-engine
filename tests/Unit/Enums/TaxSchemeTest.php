<?php

declare(strict_types=1);

use Veltix\TaxEngine\Enums\TaxScheme;

it('has all expected cases', function () {
    $cases = TaxScheme::cases();

    expect($cases)->toHaveCount(8)
        ->and($cases)->toContain(TaxScheme::Standard)
        ->and($cases)->toContain(TaxScheme::ReverseCharge)
        ->and($cases)->toContain(TaxScheme::OSS)
        ->and($cases)->toContain(TaxScheme::Export)
        ->and($cases)->toContain(TaxScheme::Exempt)
        ->and($cases)->toContain(TaxScheme::DomesticReverseCharge)
        ->and($cases)->toContain(TaxScheme::IOSS)
        ->and($cases)->toContain(TaxScheme::OutsideScope);
});

it('has correct backed values', function () {
    expect(TaxScheme::Standard->value)->toBe('standard')
        ->and(TaxScheme::ReverseCharge->value)->toBe('reverse_charge')
        ->and(TaxScheme::OSS->value)->toBe('oss')
        ->and(TaxScheme::Export->value)->toBe('export')
        ->and(TaxScheme::Exempt->value)->toBe('exempt')
        ->and(TaxScheme::DomesticReverseCharge->value)->toBe('domestic_reverse_charge')
        ->and(TaxScheme::IOSS->value)->toBe('ioss')
        ->and(TaxScheme::OutsideScope->value)->toBe('outside_scope');
});

it('can be created from value', function () {
    expect(TaxScheme::from('standard'))->toBe(TaxScheme::Standard)
        ->and(TaxScheme::from('reverse_charge'))->toBe(TaxScheme::ReverseCharge);
});

it('returns null from tryFrom for invalid value', function () {
    expect(TaxScheme::tryFrom('invalid'))->toBeNull();
});
