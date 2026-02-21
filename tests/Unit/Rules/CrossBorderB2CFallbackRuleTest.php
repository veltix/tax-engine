<?php

declare(strict_types=1);

use Veltix\TaxEngine\Data\TransactionData;
use Veltix\TaxEngine\Enums\CustomerType;
use Veltix\TaxEngine\Enums\SupplyType;
use Veltix\TaxEngine\Enums\TaxScheme;
use Veltix\TaxEngine\Repositories\StaticRateRepository;
use Veltix\TaxEngine\Rules\CrossBorderB2CFallbackRule;
use Veltix\TaxEngine\Support\Country;
use Veltix\TaxEngine\Data\TaxCalculationContext;
use Veltix\TaxEngine\Support\Money;

beforeEach(function () {
    $this->rule = new CrossBorderB2CFallbackRule(new StaticRateRepository());
});

it('applies to cross-border EU B2C transaction', function () {
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

it('does not apply to domestic transactions', function () {
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

it('does not apply to B2B transactions', function () {
    $transaction = new TransactionData(
        transactionId: 'txn-1',
        amount: Money::fromCents(10000),
        sellerCountry: new Country('DE'),
        buyerCountry: new Country('FR'),
        customerType: CustomerType::B2B,
        supplyType: SupplyType::Services,
    );

    expect($this->rule->applies($transaction, new TaxCalculationContext()))->toBeFalse();
});

it('does not apply to exports (non-EU buyer)', function () {
    $transaction = new TransactionData(
        transactionId: 'txn-1',
        amount: Money::fromCents(10000),
        sellerCountry: new Country('DE'),
        buyerCountry: new Country('US'),
        customerType: CustomerType::B2C,
        supplyType: SupplyType::Goods,
    );

    expect($this->rule->applies($transaction, new TaxCalculationContext()))->toBeFalse();
});

it('evaluates with seller country rate', function () {
    $transaction = new TransactionData(
        transactionId: 'txn-1',
        amount: Money::fromCents(10000),
        sellerCountry: new Country('DE'),
        buyerCountry: new Country('FR'),
        customerType: CustomerType::B2C,
        supplyType: SupplyType::DigitalServices,
    );

    $decision = $this->rule->evaluate($transaction, new TaxCalculationContext());

    expect($decision->scheme)->toBe(TaxScheme::Standard)
        ->and($decision->rate)->toBe('19.00')
        ->and($decision->taxCountry->code)->toBe('DE')
        ->and($decision->ruleApplied)->toBe('cross_border_b2c_fallback');
});

it('has priority 20', function () {
    expect($this->rule->priority())->toBe(20);
});
