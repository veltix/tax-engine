<?php

declare(strict_types=1);

use Veltix\TaxEngine\Data\TaxCalculationContext;
use Veltix\TaxEngine\Data\TransactionData;
use Veltix\TaxEngine\Data\VatValidationResultData;
use Veltix\TaxEngine\Enums\CustomerType;
use Veltix\TaxEngine\Enums\SupplyType;
use Veltix\TaxEngine\Enums\TaxScheme;
use Veltix\TaxEngine\Services\RulesEngineService;
use Veltix\TaxEngine\Support\Country;
use Veltix\TaxEngine\Support\Money;

it('selects export over domestic standard for EU to non-EU', function () {
    $engine = app(RulesEngineService::class);

    $decision = $engine->decide(new TransactionData(
        transactionId: 'priority-export',
        amount: Money::fromCents(10000),
        sellerCountry: new Country('DE'),
        buyerCountry: new Country('US'),
        customerType: CustomerType::B2C,
        supplyType: SupplyType::Goods,
    ));

    expect($decision->ruleApplied)->toBe('export')
        ->and($decision->scheme)->toBe(TaxScheme::Export);
});

it('selects reverse charge over OSS for cross-border B2B with VAT', function () {
    config()->set('tax.oss.enabled', true);
    $engine = app(RulesEngineService::class);

    // This is B2B with VAT number - reverse charge (50) should win over OSS (40)
    $context = new TaxCalculationContext(
        vatResult: VatValidationResultData::validResult('FR', 'FR12345678901'),
    );

    $decision = $engine->decide(new TransactionData(
        transactionId: 'priority-rc-over-oss',
        amount: Money::fromCents(10000),
        sellerCountry: new Country('DE'),
        buyerCountry: new Country('FR'),
        customerType: CustomerType::B2B,
        supplyType: SupplyType::Services,
        buyerVatNumber: 'FR12345678901',
    ), $context);

    expect($decision->ruleApplied)->toBe('reverse_charge');
});

it('selects domestic reverse charge over domestic standard when flagged', function () {
    $engine = app(RulesEngineService::class);

    $decision = $engine->decide(new TransactionData(
        transactionId: 'priority-drc',
        amount: Money::fromCents(10000),
        sellerCountry: new Country('DE'),
        buyerCountry: new Country('DE'),
        customerType: CustomerType::B2B,
        supplyType: SupplyType::Services,
        metadata: ['domestic_reverse_charge' => true],
    ));

    expect($decision->ruleApplied)->toBe('domestic_reverse_charge')
        ->and($decision->scheme)->toBe(TaxScheme::DomesticReverseCharge);
});

it('falls back to domestic standard when no higher priority rule matches', function () {
    $engine = app(RulesEngineService::class);

    $decision = $engine->decide(new TransactionData(
        transactionId: 'priority-fallback',
        amount: Money::fromCents(10000),
        sellerCountry: new Country('NL'),
        buyerCountry: new Country('NL'),
        customerType: CustomerType::B2C,
        supplyType: SupplyType::Services,
    ));

    expect($decision->ruleApplied)->toBe('domestic_standard')
        ->and($decision->scheme)->toBe(TaxScheme::Standard);
});

it('registers all 8 rules from service provider', function () {
    $engine = app(RulesEngineService::class);

    expect($engine->rules())->toHaveCount(8);
});

it('export takes priority over reverse charge for EU to non-EU B2B with VAT', function () {
    $engine = app(RulesEngineService::class);

    // Export (60) should win over reverse charge (50) even with B2B VAT
    // because isExport is true and isCrossBorderEu is false for EU→non-EU
    $decision = $engine->decide(new TransactionData(
        transactionId: 'priority-export-over-rc',
        amount: Money::fromCents(10000),
        sellerCountry: new Country('DE'),
        buyerCountry: new Country('US'),
        customerType: CustomerType::B2B,
        supplyType: SupplyType::Goods,
        buyerVatNumber: 'US12345',
    ));

    expect($decision->ruleApplied)->toBe('export');
});
