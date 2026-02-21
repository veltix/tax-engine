<?php

declare(strict_types=1);

use Veltix\TaxEngine\Actions\CalculateTaxAction;
use Veltix\TaxEngine\Contracts\RateRepositoryContract;
use Veltix\TaxEngine\Contracts\VatValidatorContract;
use Veltix\TaxEngine\Data\TransactionData;
use Veltix\TaxEngine\Enums\CustomerType;
use Veltix\TaxEngine\Enums\SupplyType;
use Veltix\TaxEngine\Enums\TaxScheme;
use Veltix\TaxEngine\Repositories\StaticRateRepository;
use Veltix\TaxEngine\Services\RulesEngineService;
use Veltix\TaxEngine\Support\Country;
use Veltix\TaxEngine\Support\Money;

it('uses default seller country NL from config', function () {
    expect(config('tax.seller.country'))->toBe('NL');
});

it('has OSS disabled by default', function () {
    expect(config('tax.oss.enabled'))->toBeFalse();
});

it('has IOSS disabled by default', function () {
    expect(config('tax.ioss.enabled'))->toBeFalse();
});

it('uses half_up rounding mode by default', function () {
    expect(config('tax.rounding.mode'))->toBe('half_up');
});

it('has compliance storage enabled by default', function () {
    expect(config('tax.compliance.store_decisions'))->toBeTrue()
        ->and(config('tax.compliance.store_evidence'))->toBeTrue();
});

it('resolves all contracts from container', function () {
    expect(app(RateRepositoryContract::class))->toBeInstanceOf(StaticRateRepository::class)
        ->and(app(VatValidatorContract::class))->not->toBeNull()
        ->and(app(RulesEngineService::class))->not->toBeNull()
        ->and(app(CalculateTaxAction::class))->not->toBeNull();
});

it('cross-border B2C uses seller country rate when OSS disabled', function () {
    // OSS is disabled by default, so cross-border B2C without OSS
    // should not match OSS rule. Without a matching rule for
    // cross-border EU B2C without OSS, the transaction won't match
    // domestic standard either. Let's verify the behavior.
    $engine = app(RulesEngineService::class);

    $transaction = new TransactionData(
        transactionId: 'config-no-oss',
        amount: Money::fromCents(10000),
        sellerCountry: new Country('DE'),
        buyerCountry: new Country('FR'),
        customerType: CustomerType::B2C,
        supplyType: SupplyType::DigitalServices,
    );

    // With OSS disabled, cross-border B2C falls back to seller country rate
    // via the CrossBorderB2CFallbackRule
    $decision = $engine->decide($transaction);

    expect($decision->ruleApplied)->toBe('cross_border_b2c_fallback')
        ->and($decision->taxCountry->code)->toBe('DE')
        ->and($decision->scheme)->toBe(TaxScheme::Standard);
});

it('all eight rules are enabled by default', function () {
    $rules = config('tax.rules');

    expect($rules)->toBe([
        'domestic_standard' => true,
        'reverse_charge' => true,
        'oss' => true,
        'export' => true,
        'domestic_reverse_charge' => true,
        'ioss' => true,
        'cross_border_b2c_fallback' => true,
        'service_export' => true,
    ]);
});

it('uses EUR as default currency', function () {
    expect(config('tax.currency'))->toBe('EUR');
});

it('has 10 year retention configured', function () {
    expect(config('tax.compliance.retention_years'))->toBe(10);
});
