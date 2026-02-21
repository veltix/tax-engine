<?php

declare(strict_types=1);

use Veltix\TaxEngine\Data\TransactionData;
use Veltix\TaxEngine\Enums\CustomerType;
use Veltix\TaxEngine\Enums\SupplyType;
use Veltix\TaxEngine\Enums\TaxScheme;
use Veltix\TaxEngine\Rules\ExportRule;
use Veltix\TaxEngine\Support\Country;
use Veltix\TaxEngine\Data\TaxCalculationContext;
use Veltix\TaxEngine\Support\Money;

beforeEach(function () {
    $this->rule = new ExportRule();
});

it('applies to EU seller with non-EU buyer', function () {
    $transaction = new TransactionData(
        transactionId: 'txn-1',
        amount: Money::fromCents(10000),
        sellerCountry: new Country('DE'),
        buyerCountry: new Country('US'),
        customerType: CustomerType::B2B,
        supplyType: SupplyType::Goods,
    );

    expect($this->rule->applies($transaction, new TaxCalculationContext()))->toBeTrue();
});

it('does not apply to domestic', function () {
    $transaction = new TransactionData(
        transactionId: 'txn-1',
        amount: Money::fromCents(10000),
        sellerCountry: new Country('DE'),
        buyerCountry: new Country('DE'),
        customerType: CustomerType::B2B,
        supplyType: SupplyType::Goods,
    );

    expect($this->rule->applies($transaction, new TaxCalculationContext()))->toBeFalse();
});

it('does not apply to cross-border EU', function () {
    $transaction = new TransactionData(
        transactionId: 'txn-1',
        amount: Money::fromCents(10000),
        sellerCountry: new Country('DE'),
        buyerCountry: new Country('FR'),
        customerType: CustomerType::B2B,
        supplyType: SupplyType::Goods,
    );

    expect($this->rule->applies($transaction, new TaxCalculationContext()))->toBeFalse();
});

it('does not apply when seller is non-EU', function () {
    $transaction = new TransactionData(
        transactionId: 'txn-1',
        amount: Money::fromCents(10000),
        sellerCountry: new Country('US'),
        buyerCountry: new Country('GB'),
        customerType: CustomerType::B2B,
        supplyType: SupplyType::Goods,
    );

    expect($this->rule->applies($transaction, new TaxCalculationContext()))->toBeFalse();
});

it('evaluates with zero rate', function () {
    $transaction = new TransactionData(
        transactionId: 'txn-1',
        amount: Money::fromCents(10000),
        sellerCountry: new Country('DE'),
        buyerCountry: new Country('US'),
        customerType: CustomerType::B2B,
        supplyType: SupplyType::Goods,
    );

    $decision = $this->rule->evaluate($transaction, new TaxCalculationContext());

    expect($decision->scheme)->toBe(TaxScheme::Export)
        ->and($decision->rate)->toBe('0.00')
        ->and($decision->taxCountry->code)->toBe('DE')
        ->and($decision->ruleApplied)->toBe('export')
        ->and($decision->isZeroRated())->toBeTrue();
});

it('does not apply to services export', function () {
    $transaction = new TransactionData(
        transactionId: 'txn-1',
        amount: Money::fromCents(10000),
        sellerCountry: new Country('DE'),
        buyerCountry: new Country('US'),
        customerType: CustomerType::B2B,
        supplyType: SupplyType::Services,
    );

    expect($this->rule->applies($transaction, new TaxCalculationContext()))->toBeFalse();
});

it('does not apply to digital services export', function () {
    $transaction = new TransactionData(
        transactionId: 'txn-1',
        amount: Money::fromCents(10000),
        sellerCountry: new Country('DE'),
        buyerCountry: new Country('US'),
        customerType: CustomerType::B2C,
        supplyType: SupplyType::DigitalServices,
    );

    expect($this->rule->applies($transaction, new TaxCalculationContext()))->toBeFalse();
});

it('has priority 60', function () {
    expect($this->rule->priority())->toBe(60);
});
