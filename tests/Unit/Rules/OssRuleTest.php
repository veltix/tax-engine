<?php

declare(strict_types=1);

use Veltix\TaxEngine\Contracts\OssTurnoverRepositoryContract;
use Veltix\TaxEngine\Data\TaxCalculationContext;
use Veltix\TaxEngine\Data\TransactionData;
use Veltix\TaxEngine\Enums\CustomerType;
use Veltix\TaxEngine\Enums\SupplyType;
use Veltix\TaxEngine\Enums\TaxScheme;
use Veltix\TaxEngine\Repositories\StaticRateRepository;
use Veltix\TaxEngine\Rules\OssRule;
use Veltix\TaxEngine\Support\Country;
use Veltix\TaxEngine\Support\Money;

beforeEach(function () {
    $this->rule = new OssRule(new StaticRateRepository(), ossEnabled: true);
});

it('applies to cross-border EU B2C when OSS enabled', function () {
    $transaction = new TransactionData(
        transactionId: 'txn-1',
        amount: Money::fromCents(10000),
        sellerCountry: new Country('DE'),
        buyerCountry: new Country('FR'),
        customerType: CustomerType::B2C,
        supplyType: SupplyType::DigitalServices,
    );

    expect($this->rule->applies($transaction, new TaxCalculationContext()))->toBeTrue();
});

it('does not apply when OSS disabled', function () {
    $rule = new OssRule(new StaticRateRepository(), ossEnabled: false);

    $transaction = new TransactionData(
        transactionId: 'txn-1',
        amount: Money::fromCents(10000),
        sellerCountry: new Country('DE'),
        buyerCountry: new Country('FR'),
        customerType: CustomerType::B2C,
        supplyType: SupplyType::DigitalServices,
    );

    expect($rule->applies($transaction, new TaxCalculationContext()))->toBeFalse();
});

it('does not apply to B2B', function () {
    $transaction = new TransactionData(
        transactionId: 'txn-1',
        amount: Money::fromCents(10000),
        sellerCountry: new Country('DE'),
        buyerCountry: new Country('FR'),
        customerType: CustomerType::B2B,
        supplyType: SupplyType::DigitalServices,
    );

    expect($this->rule->applies($transaction, new TaxCalculationContext()))->toBeFalse();
});

it('does not apply to domestic', function () {
    $transaction = new TransactionData(
        transactionId: 'txn-1',
        amount: Money::fromCents(10000),
        sellerCountry: new Country('DE'),
        buyerCountry: new Country('DE'),
        customerType: CustomerType::B2C,
        supplyType: SupplyType::DigitalServices,
    );

    expect($this->rule->applies($transaction, new TaxCalculationContext()))->toBeFalse();
});

it('evaluates with buyer country rate', function () {
    $transaction = new TransactionData(
        transactionId: 'txn-1',
        amount: Money::fromCents(10000),
        sellerCountry: new Country('DE'),
        buyerCountry: new Country('FR'),
        customerType: CustomerType::B2C,
        supplyType: SupplyType::DigitalServices,
    );

    $decision = $this->rule->evaluate($transaction, new TaxCalculationContext());

    expect($decision->scheme)->toBe(TaxScheme::OSS)
        ->and($decision->rate)->toBe('20.00')
        ->and($decision->taxCountry->code)->toBe('FR')
        ->and($decision->ruleApplied)->toBe('oss');
});

it('has priority 40', function () {
    expect($this->rule->priority())->toBe(40);
});

it('does not apply when turnover is below threshold', function () {
    $mockRepo = Mockery::mock(OssTurnoverRepositoryContract::class);
    $mockRepo->shouldReceive('rollingTwelveMonthTurnoverCents')
        ->andReturn(500000); // below 1M threshold

    $rule = new OssRule(new StaticRateRepository(), ossEnabled: true, ossThresholdCents: 1000000, turnoverRepository: $mockRepo);

    $transaction = new TransactionData(
        transactionId: 'oss-threshold-below',
        amount: Money::fromCents(10000),
        sellerCountry: new Country('DE'),
        buyerCountry: new Country('FR'),
        customerType: CustomerType::B2C,
        supplyType: SupplyType::DigitalServices,
    );

    expect($rule->applies($transaction, new TaxCalculationContext()))->toBeFalse();
});

it('applies when turnover is at threshold', function () {
    $mockRepo = Mockery::mock(OssTurnoverRepositoryContract::class);
    $mockRepo->shouldReceive('rollingTwelveMonthTurnoverCents')
        ->andReturn(1000000); // at threshold

    $rule = new OssRule(new StaticRateRepository(), ossEnabled: true, ossThresholdCents: 1000000, turnoverRepository: $mockRepo);

    $transaction = new TransactionData(
        transactionId: 'oss-threshold-at',
        amount: Money::fromCents(10000),
        sellerCountry: new Country('DE'),
        buyerCountry: new Country('FR'),
        customerType: CustomerType::B2C,
        supplyType: SupplyType::DigitalServices,
    );

    expect($rule->applies($transaction, new TaxCalculationContext()))->toBeTrue();
});

it('applies when turnover exceeds threshold', function () {
    $mockRepo = Mockery::mock(OssTurnoverRepositoryContract::class);
    $mockRepo->shouldReceive('rollingTwelveMonthTurnoverCents')
        ->andReturn(1500000); // above threshold

    $rule = new OssRule(new StaticRateRepository(), ossEnabled: true, ossThresholdCents: 1000000, turnoverRepository: $mockRepo);

    $transaction = new TransactionData(
        transactionId: 'oss-threshold-above',
        amount: Money::fromCents(10000),
        sellerCountry: new Country('DE'),
        buyerCountry: new Country('FR'),
        customerType: CustomerType::B2C,
        supplyType: SupplyType::DigitalServices,
    );

    expect($rule->applies($transaction, new TaxCalculationContext()))->toBeTrue();
});

it('assumes threshold exceeded when no repo provided (backward compatible)', function () {
    $rule = new OssRule(new StaticRateRepository(), ossEnabled: true);

    $transaction = new TransactionData(
        transactionId: 'oss-no-repo',
        amount: Money::fromCents(10000),
        sellerCountry: new Country('DE'),
        buyerCountry: new Country('FR'),
        customerType: CustomerType::B2C,
        supplyType: SupplyType::DigitalServices,
    );

    expect($rule->applies($transaction, new TaxCalculationContext()))->toBeTrue();
});
