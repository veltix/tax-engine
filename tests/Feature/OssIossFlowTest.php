<?php

declare(strict_types=1);

use Veltix\TaxEngine\Data\TaxCalculationContext;
use Veltix\TaxEngine\Data\TransactionData;
use Veltix\TaxEngine\Data\VatValidationResultData;
use Veltix\TaxEngine\Enums\CustomerType;
use Veltix\TaxEngine\Enums\SupplyType;
use Veltix\TaxEngine\Enums\TaxScheme;
use Veltix\TaxEngine\Repositories\StaticRateRepository;
use Veltix\TaxEngine\Rules\CrossBorderB2CFallbackRule;
use Veltix\TaxEngine\Rules\DomesticStandardRule;
use Veltix\TaxEngine\Rules\ExportRule;
use Veltix\TaxEngine\Rules\IossRule;
use Veltix\TaxEngine\Rules\OssRule;
use Veltix\TaxEngine\Rules\ReverseChargeRule;
use Veltix\TaxEngine\Rules\ServiceExportRule;
use Veltix\TaxEngine\Services\RulesEngineService;
use Veltix\TaxEngine\Support\Country;
use Veltix\TaxEngine\Support\Money;

function buildOssEngine(): RulesEngineService
{
    $rates = new StaticRateRepository();
    $engine = new RulesEngineService();
    $engine->addRule(new ExportRule());
    $engine->addRule(new ReverseChargeRule());
    $engine->addRule(new OssRule($rates, ossEnabled: true));
    $engine->addRule(new DomesticStandardRule($rates));

    return $engine;
}

function buildIossEngine(): RulesEngineService
{
    $rates = new StaticRateRepository();
    $engine = new RulesEngineService();
    $engine->addRule(new IossRule($rates, iossEnabled: true));
    $engine->addRule(new ExportRule());
    $engine->addRule(new DomesticStandardRule($rates));

    return $engine;
}

it('applies OSS with buyer country rate for B2C digital services', function () {
    $engine = buildOssEngine();

    $result = $engine->calculate(new TransactionData(
        transactionId: 'oss-digital-001',
        amount: Money::fromCents(10000),
        sellerCountry: new Country('NL'),
        buyerCountry: new Country('DE'),
        customerType: CustomerType::B2C,
        supplyType: SupplyType::DigitalServices,
    ));

    expect($result->decision->scheme)->toBe(TaxScheme::OSS)
        ->and($result->decision->rate)->toBe('19.00')
        ->and($result->decision->taxCountry->code)->toBe('DE')
        ->and($result->taxAmount->amount)->toBe(1900);
});

it('applies OSS with buyer country rate for B2C goods', function () {
    $engine = buildOssEngine();

    $result = $engine->calculate(new TransactionData(
        transactionId: 'oss-goods-001',
        amount: Money::fromCents(20000),
        sellerCountry: new Country('DE'),
        buyerCountry: new Country('HU'),
        customerType: CustomerType::B2C,
        supplyType: SupplyType::Goods,
    ));

    expect($result->decision->scheme)->toBe(TaxScheme::OSS)
        ->and($result->decision->rate)->toBe('27.00')
        ->and($result->decision->taxCountry->code)->toBe('HU')
        ->and($result->taxAmount->amount)->toBe(5400);
});

it('applies OSS for B2C telecom services', function () {
    $engine = buildOssEngine();

    $result = $engine->calculate(new TransactionData(
        transactionId: 'oss-telecom-001',
        amount: Money::fromCents(5000),
        sellerCountry: new Country('NL'),
        buyerCountry: new Country('FR'),
        customerType: CustomerType::B2C,
        supplyType: SupplyType::Telecommunications,
    ));

    expect($result->decision->scheme)->toBe(TaxScheme::OSS)
        ->and($result->decision->rate)->toBe('20.00')
        ->and($result->decision->taxCountry->code)->toBe('FR');
});

it('applies reverse charge over OSS for B2B with VAT', function () {
    $engine = buildOssEngine();

    $context = new TaxCalculationContext(
        vatResult: VatValidationResultData::validResult('DE', 'DE123456789'),
    );

    $result = $engine->calculate(new TransactionData(
        transactionId: 'oss-b2b-override',
        amount: Money::fromCents(10000),
        sellerCountry: new Country('NL'),
        buyerCountry: new Country('DE'),
        customerType: CustomerType::B2B,
        supplyType: SupplyType::Services,
        buyerVatNumber: 'DE123456789',
    ), $context);

    expect($result->decision->scheme)->toBe(TaxScheme::ReverseCharge)
        ->and($result->decision->rate)->toBe('0.00');
});

it('applies IOSS for non-EU goods import to EU', function () {
    $engine = buildIossEngine();

    $result = $engine->calculate(new TransactionData(
        transactionId: 'ioss-import-001',
        amount: Money::fromCents(15000),
        sellerCountry: new Country('US'),
        buyerCountry: new Country('DE'),
        customerType: CustomerType::B2C,
        supplyType: SupplyType::Goods,
    ));

    expect($result->decision->scheme)->toBe(TaxScheme::IOSS)
        ->and($result->decision->rate)->toBe('19.00')
        ->and($result->decision->taxCountry->code)->toBe('DE')
        ->and($result->taxAmount->amount)->toBe(2850);
});

it('does not apply IOSS for digital services from non-EU', function () {
    $engine = buildIossEngine();

    // IOSS only applies to goods, not digital services
    // This should fall through to export (EU seller check fails) or no match
    $transaction = new TransactionData(
        transactionId: 'ioss-digital-reject',
        amount: Money::fromCents(10000),
        sellerCountry: new Country('US'),
        buyerCountry: new Country('DE'),
        customerType: CustomerType::B2C,
        supplyType: SupplyType::DigitalServices,
    );

    expect(fn () => $engine->decide($transaction))
        ->toThrow(\Veltix\TaxEngine\Exceptions\NoApplicableRuleException::class);
});

it('applies IOSS with different EU destination rates', function (string $buyerCountry, string $expectedRate) {
    $engine = buildIossEngine();

    $result = $engine->calculate(new TransactionData(
        transactionId: "ioss-{$buyerCountry}",
        amount: Money::fromCents(10000),
        sellerCountry: new Country('US'),
        buyerCountry: new Country($buyerCountry),
        customerType: CustomerType::B2C,
        supplyType: SupplyType::Goods,
    ));

    expect($result->decision->scheme)->toBe(TaxScheme::IOSS)
        ->and($result->decision->rate)->toBe($expectedRate);
})->with([
    ['DE', '19.00'],
    ['FR', '20.00'],
    ['HU', '27.00'],
    ['LU', '17.00'],
]);

it('falls back to seller rate when OSS disabled for cross-border B2C', function () {
    $rates = new StaticRateRepository();
    $engine = new RulesEngineService();
    $engine->addRule(new ExportRule());
    $engine->addRule(new ReverseChargeRule());
    // OSS disabled — not added
    $engine->addRule(new CrossBorderB2CFallbackRule($rates));
    $engine->addRule(new DomesticStandardRule($rates));

    $result = $engine->calculate(new TransactionData(
        transactionId: 'oss-disabled-fallback',
        amount: Money::fromCents(10000),
        sellerCountry: new Country('DE'),
        buyerCountry: new Country('FR'),
        customerType: CustomerType::B2C,
        supplyType: SupplyType::DigitalServices,
    ));

    expect($result->decision->ruleApplied)->toBe('cross_border_b2c_fallback')
        ->and($result->decision->taxCountry->code)->toBe('DE')
        ->and($result->decision->rate)->toBe('19.00');
});

it('does not apply IOSS when EUR amount exceeds 150 threshold', function () {
    $rates = new StaticRateRepository();
    $engine = new RulesEngineService();
    $engine->addRule(new IossRule($rates, iossEnabled: true, iossConsignmentMaxCents: 15000));
    $engine->addRule(new DomesticStandardRule($rates));

    $transaction = new TransactionData(
        transactionId: 'ioss-over-threshold',
        amount: Money::fromCents(20000, 'EUR'),
        sellerCountry: new Country('US'),
        buyerCountry: new Country('DE'),
        customerType: CustomerType::B2C,
        supplyType: SupplyType::Goods,
    );

    // IOSS won't apply (over threshold), and no other rule matches
    expect(fn () => $engine->decide($transaction))
        ->toThrow(\Veltix\TaxEngine\Exceptions\NoApplicableRuleException::class);
});

it('applies ServiceExportRule for services to non-EU', function () {
    $rates = new StaticRateRepository();
    $engine = new RulesEngineService();
    $engine->addRule(new ExportRule());
    $engine->addRule(new ServiceExportRule());
    $engine->addRule(new DomesticStandardRule($rates));

    $result = $engine->calculate(new TransactionData(
        transactionId: 'service-export-001',
        amount: Money::fromCents(10000),
        sellerCountry: new Country('DE'),
        buyerCountry: new Country('US'),
        customerType: CustomerType::B2B,
        supplyType: SupplyType::Services,
    ));

    expect($result->decision->ruleApplied)->toBe('service_export')
        ->and($result->decision->scheme)->toBe(TaxScheme::OutsideScope)
        ->and($result->decision->rate)->toBe('0.00');
});
