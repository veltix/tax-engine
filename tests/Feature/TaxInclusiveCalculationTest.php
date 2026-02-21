<?php

declare(strict_types=1);

use Veltix\TaxEngine\Data\TaxCalculationContext;
use Veltix\TaxEngine\Data\TransactionData;
use Veltix\TaxEngine\Enums\CustomerType;
use Veltix\TaxEngine\Enums\PriceMode;
use Veltix\TaxEngine\Enums\SupplyType;
use Veltix\TaxEngine\Enums\TaxScheme;
use Veltix\TaxEngine\Repositories\StaticRateRepository;
use Veltix\TaxEngine\Rules\DomesticStandardRule;
use Veltix\TaxEngine\Services\RulesEngineService;
use Veltix\TaxEngine\Support\Country;
use Veltix\TaxEngine\Support\Money;

it('reverse-calculates net from gross for tax-inclusive mode', function () {
    $engine = new RulesEngineService();
    $engine->addRule(new DomesticStandardRule(new StaticRateRepository()));

    $transaction = new TransactionData(
        transactionId: 'inclusive-001',
        amount: Money::fromCents(11900), // gross amount including 19% VAT
        sellerCountry: new Country('DE'),
        buyerCountry: new Country('DE'),
        customerType: CustomerType::B2C,
        supplyType: SupplyType::Goods,
    );

    $context = new TaxCalculationContext(priceMode: PriceMode::TaxInclusive);

    $result = $engine->calculate($transaction, $context);

    expect($result->grossAmount->amount)->toBe(11900)
        ->and($result->netAmount->amount)->toBe(10000)
        ->and($result->taxAmount->amount)->toBe(1900)
        ->and($result->decision->scheme)->toBe(TaxScheme::Standard)
        ->and($result->decision->rate)->toBe('19.00');
});

it('handles tax-inclusive with zero rate (export)', function () {
    $engine = new RulesEngineService();
    $engine->addRule(new \Veltix\TaxEngine\Rules\ExportRule());

    $transaction = new TransactionData(
        transactionId: 'inclusive-002',
        amount: Money::fromCents(10000),
        sellerCountry: new Country('DE'),
        buyerCountry: new Country('US'),
        customerType: CustomerType::B2B,
        supplyType: SupplyType::Goods,
    );

    $context = new TaxCalculationContext(priceMode: PriceMode::TaxInclusive);

    $result = $engine->calculate($transaction, $context);

    expect($result->grossAmount->amount)->toBe(10000)
        ->and($result->netAmount->amount)->toBe(10000)
        ->and($result->taxAmount->amount)->toBe(0);
});

it('handles tax-inclusive with Hungary 27% rate', function () {
    $engine = new RulesEngineService();
    $engine->addRule(new DomesticStandardRule(new StaticRateRepository()));

    // 12700 = 10000 net + 2700 tax (27%)
    $transaction = new TransactionData(
        transactionId: 'inclusive-003',
        amount: Money::fromCents(12700),
        sellerCountry: new Country('HU'),
        buyerCountry: new Country('HU'),
        customerType: CustomerType::B2C,
        supplyType: SupplyType::Goods,
    );

    $context = new TaxCalculationContext(priceMode: PriceMode::TaxInclusive);

    $result = $engine->calculate($transaction, $context);

    expect($result->grossAmount->amount)->toBe(12700)
        ->and($result->netAmount->amount)->toBe(10000)
        ->and($result->taxAmount->amount)->toBe(2700)
        ->and($result->decision->rate)->toBe('27.00');
});

it('tax-exclusive mode preserves existing behavior', function () {
    $engine = new RulesEngineService();
    $engine->addRule(new DomesticStandardRule(new StaticRateRepository()));

    $transaction = new TransactionData(
        transactionId: 'exclusive-001',
        amount: Money::fromCents(10000),
        sellerCountry: new Country('DE'),
        buyerCountry: new Country('DE'),
        customerType: CustomerType::B2C,
        supplyType: SupplyType::Goods,
    );

    $context = new TaxCalculationContext(priceMode: PriceMode::TaxExclusive);

    $result = $engine->calculate($transaction, $context);

    expect($result->netAmount->amount)->toBe(10000)
        ->and($result->taxAmount->amount)->toBe(1900)
        ->and($result->grossAmount->amount)->toBe(11900);
});
