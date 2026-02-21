<?php

declare(strict_types=1);

use Veltix\TaxEngine\Contracts\OssTurnoverRepositoryContract;
use Veltix\TaxEngine\Data\TaxCalculationContext;
use Veltix\TaxEngine\Data\TransactionData;
use Veltix\TaxEngine\Enums\CustomerType;
use Veltix\TaxEngine\Enums\SupplyType;
use Veltix\TaxEngine\Enums\TaxScheme;
use Veltix\TaxEngine\Repositories\StaticRateRepository;
use Veltix\TaxEngine\Rules\CrossBorderB2CFallbackRule;
use Veltix\TaxEngine\Rules\DomesticStandardRule;
use Veltix\TaxEngine\Rules\OssRule;
use Veltix\TaxEngine\Services\RulesEngineService;
use Veltix\TaxEngine\Support\Country;
use Veltix\TaxEngine\Support\Money;

it('applies OSS when turnover exceeds threshold', function () {
    $mockRepo = Mockery::mock(OssTurnoverRepositoryContract::class);
    $mockRepo->shouldReceive('rollingTwelveMonthTurnoverCents')
        ->andReturn(1500000);

    $rates = new StaticRateRepository();
    $engine = new RulesEngineService();
    $engine->addRule(new OssRule($rates, ossEnabled: true, ossThresholdCents: 1000000, turnoverRepository: $mockRepo));
    $engine->addRule(new CrossBorderB2CFallbackRule($rates));
    $engine->addRule(new DomesticStandardRule($rates));

    $result = $engine->calculate(new TransactionData(
        transactionId: 'oss-over-threshold',
        amount: Money::fromCents(10000),
        sellerCountry: new Country('DE'),
        buyerCountry: new Country('FR'),
        customerType: CustomerType::B2C,
        supplyType: SupplyType::DigitalServices,
    ));

    expect($result->decision->scheme)->toBe(TaxScheme::OSS)
        ->and($result->decision->taxCountry->code)->toBe('FR')
        ->and($result->decision->rate)->toBe('20.00');
});

it('falls back to seller rate when turnover is below threshold', function () {
    $mockRepo = Mockery::mock(OssTurnoverRepositoryContract::class);
    $mockRepo->shouldReceive('rollingTwelveMonthTurnoverCents')
        ->andReturn(500000);

    $rates = new StaticRateRepository();
    $engine = new RulesEngineService();
    $engine->addRule(new OssRule($rates, ossEnabled: true, ossThresholdCents: 1000000, turnoverRepository: $mockRepo));
    $engine->addRule(new CrossBorderB2CFallbackRule($rates));
    $engine->addRule(new DomesticStandardRule($rates));

    $result = $engine->calculate(new TransactionData(
        transactionId: 'oss-under-threshold',
        amount: Money::fromCents(10000),
        sellerCountry: new Country('DE'),
        buyerCountry: new Country('FR'),
        customerType: CustomerType::B2C,
        supplyType: SupplyType::DigitalServices,
    ));

    expect($result->decision->scheme)->toBe(TaxScheme::Standard)
        ->and($result->decision->ruleApplied)->toBe('cross_border_b2c_fallback')
        ->and($result->decision->taxCountry->code)->toBe('DE');
});
