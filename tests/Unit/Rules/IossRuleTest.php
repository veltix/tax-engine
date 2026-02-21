<?php

declare(strict_types=1);

use Veltix\TaxEngine\Data\LegalEntityData;
use Veltix\TaxEngine\Data\TaxCalculationContext;
use Veltix\TaxEngine\Data\TransactionData;
use Veltix\TaxEngine\Enums\CustomerType;
use Veltix\TaxEngine\Enums\SupplyType;
use Veltix\TaxEngine\Enums\TaxScheme;
use Veltix\TaxEngine\Repositories\StaticRateRepository;
use Veltix\TaxEngine\Rules\IossRule;
use Veltix\TaxEngine\Support\Country;
use Veltix\TaxEngine\Support\Money;

beforeEach(function () {
    $this->rule = new IossRule(new StaticRateRepository(), iossEnabled: true);
});

it('applies to non-EU seller importing goods to EU buyer', function () {
    $transaction = new TransactionData(
        transactionId: 'txn-1',
        amount: Money::fromCents(10000),
        sellerCountry: new Country('US'),
        buyerCountry: new Country('DE'),
        customerType: CustomerType::B2C,
        supplyType: SupplyType::Goods,
    );

    expect($this->rule->applies($transaction, new TaxCalculationContext()))->toBeTrue();
});

it('does not apply when IOSS disabled', function () {
    $rule = new IossRule(new StaticRateRepository(), iossEnabled: false);

    $transaction = new TransactionData(
        transactionId: 'txn-1',
        amount: Money::fromCents(10000),
        sellerCountry: new Country('US'),
        buyerCountry: new Country('DE'),
        customerType: CustomerType::B2C,
        supplyType: SupplyType::Goods,
    );

    expect($rule->applies($transaction, new TaxCalculationContext()))->toBeFalse();
});

it('does not apply to digital services', function () {
    $transaction = new TransactionData(
        transactionId: 'txn-1',
        amount: Money::fromCents(10000),
        sellerCountry: new Country('US'),
        buyerCountry: new Country('DE'),
        customerType: CustomerType::B2C,
        supplyType: SupplyType::DigitalServices,
    );

    expect($this->rule->applies($transaction, new TaxCalculationContext()))->toBeFalse();
});

it('does not apply when buyer is non-EU', function () {
    $transaction = new TransactionData(
        transactionId: 'txn-1',
        amount: Money::fromCents(10000),
        sellerCountry: new Country('US'),
        buyerCountry: new Country('GB'),
        customerType: CustomerType::B2C,
        supplyType: SupplyType::Goods,
    );

    expect($this->rule->applies($transaction, new TaxCalculationContext()))->toBeFalse();
});

it('does not apply when seller is EU', function () {
    $transaction = new TransactionData(
        transactionId: 'txn-1',
        amount: Money::fromCents(10000),
        sellerCountry: new Country('FR'),
        buyerCountry: new Country('DE'),
        customerType: CustomerType::B2C,
        supplyType: SupplyType::Goods,
    );

    expect($this->rule->applies($transaction, new TaxCalculationContext()))->toBeFalse();
});

it('evaluates with buyer country rate', function () {
    $transaction = new TransactionData(
        transactionId: 'txn-1',
        amount: Money::fromCents(10000),
        sellerCountry: new Country('US'),
        buyerCountry: new Country('DE'),
        customerType: CustomerType::B2C,
        supplyType: SupplyType::Goods,
    );

    $decision = $this->rule->evaluate($transaction, new TaxCalculationContext());

    expect($decision->scheme)->toBe(TaxScheme::IOSS)
        ->and($decision->rate)->toBe('19.00')
        ->and($decision->taxCountry->code)->toBe('DE')
        ->and($decision->ruleApplied)->toBe('ioss');
});

it('applies when EUR amount is exactly at threshold (150.00)', function () {
    $transaction = new TransactionData(
        transactionId: 'txn-1',
        amount: Money::fromCents(15000, 'EUR'),
        sellerCountry: new Country('US'),
        buyerCountry: new Country('DE'),
        customerType: CustomerType::B2C,
        supplyType: SupplyType::Goods,
    );

    expect($this->rule->applies($transaction, new TaxCalculationContext()))->toBeTrue();
});

it('does not apply when EUR amount exceeds 150 threshold', function () {
    $rule = new IossRule(new StaticRateRepository(), iossEnabled: true, iossConsignmentMaxCents: 15000);

    $transaction = new TransactionData(
        transactionId: 'txn-1',
        amount: Money::fromCents(15001, 'EUR'),
        sellerCountry: new Country('US'),
        buyerCountry: new Country('DE'),
        customerType: CustomerType::B2C,
        supplyType: SupplyType::Goods,
    );

    expect($rule->applies($transaction, new TaxCalculationContext()))->toBeFalse();
});

it('applies when EUR amount is below threshold', function () {
    $rule = new IossRule(new StaticRateRepository(), iossEnabled: true, iossConsignmentMaxCents: 15000);

    $transaction = new TransactionData(
        transactionId: 'txn-1',
        amount: Money::fromCents(14999, 'EUR'),
        sellerCountry: new Country('US'),
        buyerCountry: new Country('DE'),
        customerType: CustomerType::B2C,
        supplyType: SupplyType::Goods,
    );

    expect($rule->applies($transaction, new TaxCalculationContext()))->toBeTrue();
});

it('does not enforce threshold for non-EUR currency', function () {
    $rule = new IossRule(new StaticRateRepository(), iossEnabled: true, iossConsignmentMaxCents: 15000);

    $transaction = new TransactionData(
        transactionId: 'txn-1',
        amount: Money::fromCents(99999, 'USD'),
        sellerCountry: new Country('US'),
        buyerCountry: new Country('DE'),
        customerType: CustomerType::B2C,
        supplyType: SupplyType::Goods,
    );

    expect($rule->applies($transaction, new TaxCalculationContext()))->toBeTrue();
});

it('has priority 70', function () {
    expect($this->rule->priority())->toBe(70);
});

it('does not apply for excluded category', function () {
    $rule = new IossRule(new StaticRateRepository(), iossEnabled: true, iossExcludedCategories: ['excise']);

    $transaction = new TransactionData(
        transactionId: 'ioss-excluded',
        amount: Money::fromCents(10000),
        sellerCountry: new Country('US'),
        buyerCountry: new Country('DE'),
        customerType: CustomerType::B2C,
        supplyType: SupplyType::Goods,
        metadata: ['rate_category' => 'excise'],
    );

    expect($rule->applies($transaction, new TaxCalculationContext()))->toBeFalse();
});

it('uses consignment_value_cents from metadata when present', function () {
    $rule = new IossRule(new StaticRateRepository(), iossEnabled: true, iossConsignmentMaxCents: 15000);

    // Transaction amount is below threshold but consignment value exceeds it
    $transaction = new TransactionData(
        transactionId: 'ioss-consignment',
        amount: Money::fromCents(10000, 'EUR'),
        sellerCountry: new Country('US'),
        buyerCountry: new Country('DE'),
        customerType: CustomerType::B2C,
        supplyType: SupplyType::Goods,
        metadata: ['consignment_value_cents' => 20000],
    );

    expect($rule->applies($transaction, new TaxCalculationContext()))->toBeFalse();
});

it('applies when consignment_value_cents is within threshold', function () {
    $rule = new IossRule(new StaticRateRepository(), iossEnabled: true, iossConsignmentMaxCents: 15000);

    $transaction = new TransactionData(
        transactionId: 'ioss-consignment-ok',
        amount: Money::fromCents(10000, 'EUR'),
        sellerCountry: new Country('US'),
        buyerCountry: new Country('DE'),
        customerType: CustomerType::B2C,
        supplyType: SupplyType::Goods,
        metadata: ['consignment_value_cents' => 12000],
    );

    expect($rule->applies($transaction, new TaxCalculationContext()))->toBeTrue();
});

it('does not apply when legal entity is not IOSS registered', function () {
    $context = new TaxCalculationContext(
        legalEntity: new LegalEntityData(
            country: new Country('US'),
            iossRegistered: false,
        ),
    );

    $transaction = new TransactionData(
        transactionId: 'ioss-no-reg',
        amount: Money::fromCents(10000),
        sellerCountry: new Country('US'),
        buyerCountry: new Country('DE'),
        customerType: CustomerType::B2C,
        supplyType: SupplyType::Goods,
    );

    expect($this->rule->applies($transaction, $context))->toBeFalse();
});

it('applies when legal entity is IOSS registered', function () {
    $context = new TaxCalculationContext(
        legalEntity: new LegalEntityData(
            country: new Country('US'),
            iossRegistered: true,
        ),
    );

    $transaction = new TransactionData(
        transactionId: 'ioss-registered',
        amount: Money::fromCents(10000),
        sellerCountry: new Country('US'),
        buyerCountry: new Country('DE'),
        customerType: CustomerType::B2C,
        supplyType: SupplyType::Goods,
    );

    expect($this->rule->applies($transaction, $context))->toBeTrue();
});
