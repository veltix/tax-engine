<?php

declare(strict_types=1);

use Veltix\TaxEngine\Data\TransactionData;
use Veltix\TaxEngine\Enums\CustomerType;
use Veltix\TaxEngine\Enums\SupplyType;
use Veltix\TaxEngine\Enums\TaxScheme;
use Veltix\TaxEngine\Repositories\StaticRateRepository;
use Veltix\TaxEngine\Rules\DomesticStandardRule;
use Veltix\TaxEngine\Support\Country;
use Veltix\TaxEngine\Data\TaxCalculationContext;
use Veltix\TaxEngine\Support\Money;

beforeEach(function () {
    $this->rule = new DomesticStandardRule(new StaticRateRepository());
});

it('applies to domestic EU transactions', function () {
    $transaction = new TransactionData(
        transactionId: 'txn-1',
        amount: Money::fromCents(10000),
        sellerCountry: new Country('DE'),
        buyerCountry: new Country('DE'),
        customerType: CustomerType::B2C,
        supplyType: SupplyType::Goods,
    );

    expect($this->rule->applies($transaction, new TaxCalculationContext()))->toBeTrue();
});

it('does not apply to cross-border EU', function () {
    $transaction = new TransactionData(
        transactionId: 'txn-1',
        amount: Money::fromCents(10000),
        sellerCountry: new Country('DE'),
        buyerCountry: new Country('FR'),
        customerType: CustomerType::B2C,
        supplyType: SupplyType::Goods,
    );

    expect($this->rule->applies($transaction, new TaxCalculationContext()))->toBeFalse();
});

it('does not apply when seller is non-EU', function () {
    $transaction = new TransactionData(
        transactionId: 'txn-1',
        amount: Money::fromCents(10000),
        sellerCountry: new Country('US'),
        buyerCountry: new Country('US'),
        customerType: CustomerType::B2C,
        supplyType: SupplyType::Goods,
    );

    expect($this->rule->applies($transaction, new TaxCalculationContext()))->toBeFalse();
});

it('evaluates with standard rate', function () {
    $transaction = new TransactionData(
        transactionId: 'txn-1',
        amount: Money::fromCents(10000),
        sellerCountry: new Country('DE'),
        buyerCountry: new Country('DE'),
        customerType: CustomerType::B2C,
        supplyType: SupplyType::Goods,
    );

    $decision = $this->rule->evaluate($transaction, new TaxCalculationContext());

    expect($decision->scheme)->toBe(TaxScheme::Standard)
        ->and($decision->rate)->toBe('19.00')
        ->and($decision->taxCountry->code)->toBe('DE')
        ->and($decision->ruleApplied)->toBe('domestic_standard');
});

it('evaluates with reduced rate via category', function () {
    $transaction = new TransactionData(
        transactionId: 'txn-1',
        amount: Money::fromCents(10000),
        sellerCountry: new Country('DE'),
        buyerCountry: new Country('DE'),
        customerType: CustomerType::B2C,
        supplyType: SupplyType::Goods,
        metadata: ['rate_category' => 'reduced'],
    );

    $decision = $this->rule->evaluate($transaction, new TaxCalculationContext());

    expect($decision->rate)->toBe('7.00');
});

it('has priority 10', function () {
    expect($this->rule->priority())->toBe(10);
});
