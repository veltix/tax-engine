<?php

declare(strict_types=1);

use Veltix\TaxEngine\Data\LegalEntityData;
use Veltix\TaxEngine\Data\TaxCalculationContext;
use Veltix\TaxEngine\Data\VatValidationResultData;
use Veltix\TaxEngine\Enums\PriceMode;
use Veltix\TaxEngine\Enums\RoundingStrategy;
use Veltix\TaxEngine\Support\Country;

it('creates with defaults', function () {
    $context = new TaxCalculationContext();

    expect($context->vatResult)->toBeNull()
        ->and($context->priceMode)->toBe(PriceMode::TaxExclusive)
        ->and($context->roundingStrategy)->toBe(RoundingStrategy::PerLine)
        ->and($context->legalEntity)->toBeNull();
});

it('creates with all parameters', function () {
    $vatResult = VatValidationResultData::validResult('DE', 'DE123456789');
    $legalEntity = new LegalEntityData(
        country: new Country('NL'),
        vatNumber: 'NL123456789B01',
        ossRegistered: true,
        iossRegistered: false,
    );

    $context = new TaxCalculationContext(
        vatResult: $vatResult,
        priceMode: PriceMode::TaxInclusive,
        roundingStrategy: RoundingStrategy::PerInvoice,
        legalEntity: $legalEntity,
    );

    expect($context->vatResult->valid)->toBeTrue()
        ->and($context->priceMode)->toBe(PriceMode::TaxInclusive)
        ->and($context->roundingStrategy)->toBe(RoundingStrategy::PerInvoice)
        ->and($context->legalEntity->country->code)->toBe('NL')
        ->and($context->legalEntity->ossRegistered)->toBeTrue();
});

it('serializes to array', function () {
    $context = new TaxCalculationContext(
        vatResult: VatValidationResultData::validResult('DE', 'DE123456789'),
        priceMode: PriceMode::TaxInclusive,
    );

    $array = $context->toArray();

    expect($array)->toHaveKeys(['vatResult', 'priceMode', 'roundingStrategy', 'legalEntity'])
        ->and($array['priceMode'])->toBe('tax_inclusive')
        ->and($array['vatResult']['valid'])->toBeTrue();
});
