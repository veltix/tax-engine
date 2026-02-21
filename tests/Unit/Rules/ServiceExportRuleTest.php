<?php

declare(strict_types=1);

use Veltix\TaxEngine\Data\TransactionData;
use Veltix\TaxEngine\Enums\CustomerType;
use Veltix\TaxEngine\Enums\SupplyType;
use Veltix\TaxEngine\Enums\TaxScheme;
use Veltix\TaxEngine\Rules\ServiceExportRule;
use Veltix\TaxEngine\Support\Country;
use Veltix\TaxEngine\Data\TaxCalculationContext;
use Veltix\TaxEngine\Support\Money;

beforeEach(function () {
    $this->rule = new ServiceExportRule();
});

it('applies to EU seller exporting services to non-EU buyer', function () {
    $transaction = new TransactionData(
        transactionId: 'txn-1',
        amount: Money::fromCents(10000),
        sellerCountry: new Country('DE'),
        buyerCountry: new Country('US'),
        customerType: CustomerType::B2B,
        supplyType: SupplyType::Services,
    );

    expect($this->rule->applies($transaction, new TaxCalculationContext()))->toBeTrue();
});

it('applies to digital services export', function () {
    $transaction = new TransactionData(
        transactionId: 'txn-1',
        amount: Money::fromCents(10000),
        sellerCountry: new Country('DE'),
        buyerCountry: new Country('US'),
        customerType: CustomerType::B2C,
        supplyType: SupplyType::DigitalServices,
    );

    expect($this->rule->applies($transaction, new TaxCalculationContext()))->toBeTrue();
});

it('does not apply to goods export', function () {
    $transaction = new TransactionData(
        transactionId: 'txn-1',
        amount: Money::fromCents(10000),
        sellerCountry: new Country('DE'),
        buyerCountry: new Country('US'),
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
        supplyType: SupplyType::Services,
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
        supplyType: SupplyType::Services,
    );

    expect($this->rule->applies($transaction, new TaxCalculationContext()))->toBeFalse();
});

it('evaluates with OutsideScope scheme and zero rate', function () {
    $transaction = new TransactionData(
        transactionId: 'txn-1',
        amount: Money::fromCents(10000),
        sellerCountry: new Country('DE'),
        buyerCountry: new Country('US'),
        customerType: CustomerType::B2B,
        supplyType: SupplyType::Services,
    );

    $decision = $this->rule->evaluate($transaction, new TaxCalculationContext());

    expect($decision->scheme)->toBe(TaxScheme::OutsideScope)
        ->and($decision->rate)->toBe('0.00')
        ->and($decision->taxCountry->code)->toBe('DE')
        ->and($decision->ruleApplied)->toBe('service_export')
        ->and($decision->isZeroRated())->toBeTrue();
});

it('has priority 59', function () {
    expect($this->rule->priority())->toBe(59);
});
