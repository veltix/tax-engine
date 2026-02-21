<?php

declare(strict_types=1);

use Veltix\TaxEngine\Data\TaxCalculationContext;
use Veltix\TaxEngine\Data\TransactionData;
use Veltix\TaxEngine\Data\VatValidationResultData;
use Veltix\TaxEngine\Enums\CustomerType;
use Veltix\TaxEngine\Enums\SupplyType;
use Veltix\TaxEngine\Enums\TaxScheme;
use Veltix\TaxEngine\Exceptions\NoApplicableRuleException;
use Veltix\TaxEngine\Repositories\StaticRateRepository;
use Veltix\TaxEngine\Rules\DomesticReverseChargeRule;
use Veltix\TaxEngine\Rules\DomesticStandardRule;
use Veltix\TaxEngine\Rules\ExportRule;
use Veltix\TaxEngine\Rules\IossRule;
use Veltix\TaxEngine\Rules\OssRule;
use Veltix\TaxEngine\Rules\ReverseChargeRule;
use Veltix\TaxEngine\Services\RulesEngineService;
use Veltix\TaxEngine\Support\Country;
use Veltix\TaxEngine\Support\Money;

function createEngine(bool $ossEnabled = false, bool $iossEnabled = false): RulesEngineService
{
    $rates = new StaticRateRepository();
    $engine = new RulesEngineService();

    $engine->addRule(new IossRule($rates, iossEnabled: $iossEnabled));
    $engine->addRule(new ExportRule());
    $engine->addRule(new ReverseChargeRule());
    $engine->addRule(new OssRule($rates, ossEnabled: $ossEnabled));
    $engine->addRule(new DomesticReverseChargeRule());
    $engine->addRule(new DomesticStandardRule($rates));

    return $engine;
}

it('decides domestic standard VAT', function () {
    $engine = createEngine();

    $transaction = new TransactionData(
        transactionId: 'txn-1',
        amount: Money::fromCents(10000),
        sellerCountry: new Country('DE'),
        buyerCountry: new Country('DE'),
        customerType: CustomerType::B2C,
        supplyType: SupplyType::Goods,
    );

    $decision = $engine->decide($transaction);

    expect($decision->scheme)->toBe(TaxScheme::Standard)
        ->and($decision->rate)->toBe('19.00')
        ->and($decision->taxCountry->code)->toBe('DE');
});

it('decides reverse charge for cross-border B2B with VAT', function () {
    $engine = createEngine();

    $transaction = new TransactionData(
        transactionId: 'txn-1',
        amount: Money::fromCents(10000),
        sellerCountry: new Country('DE'),
        buyerCountry: new Country('FR'),
        customerType: CustomerType::B2B,
        supplyType: SupplyType::Services,
        buyerVatNumber: 'FR12345678901',
    );

    $context = new TaxCalculationContext(
        vatResult: VatValidationResultData::validResult('FR', 'FR12345678901'),
    );

    $decision = $engine->decide($transaction, $context);

    expect($decision->scheme)->toBe(TaxScheme::ReverseCharge)
        ->and($decision->rate)->toBe('0.00')
        ->and($decision->reverseCharged)->toBeTrue();
});

it('decides export for EU to non-EU', function () {
    $engine = createEngine();

    $transaction = new TransactionData(
        transactionId: 'txn-1',
        amount: Money::fromCents(10000),
        sellerCountry: new Country('DE'),
        buyerCountry: new Country('US'),
        customerType: CustomerType::B2B,
        supplyType: SupplyType::Goods,
    );

    $decision = $engine->decide($transaction);

    expect($decision->scheme)->toBe(TaxScheme::Export)
        ->and($decision->rate)->toBe('0.00');
});

it('decides OSS for cross-border B2C when enabled', function () {
    $engine = createEngine(ossEnabled: true);

    $transaction = new TransactionData(
        transactionId: 'txn-1',
        amount: Money::fromCents(10000),
        sellerCountry: new Country('DE'),
        buyerCountry: new Country('FR'),
        customerType: CustomerType::B2C,
        supplyType: SupplyType::DigitalServices,
    );

    $decision = $engine->decide($transaction);

    expect($decision->scheme)->toBe(TaxScheme::OSS)
        ->and($decision->rate)->toBe('20.00')
        ->and($decision->taxCountry->code)->toBe('FR');
});

it('decides IOSS for non-EU goods import when enabled', function () {
    $engine = createEngine(iossEnabled: true);

    $transaction = new TransactionData(
        transactionId: 'txn-1',
        amount: Money::fromCents(10000),
        sellerCountry: new Country('US'),
        buyerCountry: new Country('DE'),
        customerType: CustomerType::B2C,
        supplyType: SupplyType::Goods,
    );

    $decision = $engine->decide($transaction);

    expect($decision->scheme)->toBe(TaxScheme::IOSS)
        ->and($decision->rate)->toBe('19.00')
        ->and($decision->taxCountry->code)->toBe('DE');
});

it('decides domestic reverse charge with metadata flag', function () {
    $engine = createEngine();

    $transaction = new TransactionData(
        transactionId: 'txn-1',
        amount: Money::fromCents(10000),
        sellerCountry: new Country('DE'),
        buyerCountry: new Country('DE'),
        customerType: CustomerType::B2B,
        supplyType: SupplyType::Services,
        metadata: ['domestic_reverse_charge' => true],
    );

    $decision = $engine->decide($transaction);

    expect($decision->scheme)->toBe(TaxScheme::DomesticReverseCharge)
        ->and($decision->reverseCharged)->toBeTrue();
});

it('throws NoApplicableRuleException when no rule matches', function () {
    $engine = new RulesEngineService();

    $transaction = new TransactionData(
        transactionId: 'txn-1',
        amount: Money::fromCents(10000),
        sellerCountry: new Country('US'),
        buyerCountry: new Country('GB'),
        customerType: CustomerType::B2C,
        supplyType: SupplyType::Goods,
    );

    $engine->decide($transaction);
})->throws(NoApplicableRuleException::class, 'No applicable tax rule found for transaction: txn-1');

it('respects priority order — higher priority evaluated first', function () {
    $engine = createEngine();

    // Export (priority 60) should beat reverse charge (priority 50) for EU→non-EU B2B
    $transaction = new TransactionData(
        transactionId: 'txn-1',
        amount: Money::fromCents(10000),
        sellerCountry: new Country('DE'),
        buyerCountry: new Country('US'),
        customerType: CustomerType::B2B,
        supplyType: SupplyType::Goods,
        buyerVatNumber: 'US123456',
    );

    $decision = $engine->decide($transaction);

    expect($decision->ruleApplied)->toBe('export');
});

it('calculates tax result with correct amounts', function () {
    $engine = createEngine();

    $transaction = new TransactionData(
        transactionId: 'txn-1',
        amount: Money::fromCents(10000),
        sellerCountry: new Country('DE'),
        buyerCountry: new Country('DE'),
        customerType: CustomerType::B2C,
        supplyType: SupplyType::Goods,
    );

    $result = $engine->calculate($transaction);

    expect($result->netAmount->amount)->toBe(10000)
        ->and($result->taxAmount->amount)->toBe(1900)
        ->and($result->grossAmount->amount)->toBe(11900)
        ->and($result->decision->scheme)->toBe(TaxScheme::Standard)
        ->and($result->transactionId)->toBe('txn-1');
});

it('calculates zero tax for export', function () {
    $engine = createEngine();

    $transaction = new TransactionData(
        transactionId: 'txn-1',
        amount: Money::fromCents(10000),
        sellerCountry: new Country('DE'),
        buyerCountry: new Country('US'),
        customerType: CustomerType::B2B,
        supplyType: SupplyType::Goods,
    );

    $result = $engine->calculate($transaction);

    expect($result->taxAmount->amount)->toBe(0)
        ->and($result->grossAmount->amount)->toBe(10000);
});

it('calculates with reduced rate category', function () {
    $engine = createEngine();

    $transaction = new TransactionData(
        transactionId: 'txn-1',
        amount: Money::fromCents(10000),
        sellerCountry: new Country('DE'),
        buyerCountry: new Country('DE'),
        customerType: CustomerType::B2C,
        supplyType: SupplyType::Goods,
        metadata: ['rate_category' => 'reduced'],
    );

    $result = $engine->calculate($transaction);

    expect($result->taxAmount->amount)->toBe(700)
        ->and($result->decision->rate)->toBe('7.00');
});

it('tracks added rules', function () {
    $engine = new RulesEngineService();

    expect($engine->rules())->toHaveCount(0);

    $engine->addRule(new ExportRule());
    $engine->addRule(new ReverseChargeRule());

    expect($engine->rules())->toHaveCount(2);
});
