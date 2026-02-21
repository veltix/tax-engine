<?php

declare(strict_types=1);

use Veltix\TaxEngine\Data\TaxCalculationContext;
use Veltix\TaxEngine\Data\TransactionData;
use Veltix\TaxEngine\Data\VatValidationResultData;
use Veltix\TaxEngine\Enums\CustomerType;
use Veltix\TaxEngine\Enums\SupplyType;
use Veltix\TaxEngine\Enums\TaxScheme;
use Veltix\TaxEngine\Rules\ReverseChargeRule;
use Veltix\TaxEngine\Support\Country;
use Veltix\TaxEngine\Support\Money;

beforeEach(function () {
    $this->rule = new ReverseChargeRule();
});

function reverseChargeTransaction(
    string $buyerVatNumber = 'FR12345678901',
    CustomerType $customerType = CustomerType::B2B,
    string $sellerCountry = 'DE',
    string $buyerCountry = 'FR',
): TransactionData {
    return new TransactionData(
        transactionId: 'txn-rc',
        amount: Money::fromCents(10000),
        sellerCountry: new Country($sellerCountry),
        buyerCountry: new Country($buyerCountry),
        customerType: $customerType,
        supplyType: SupplyType::Services,
        buyerVatNumber: $buyerVatNumber,
    );
}

function validVatContext(): TaxCalculationContext
{
    return new TaxCalculationContext(
        vatResult: VatValidationResultData::validResult('FR', 'FR12345678901'),
    );
}

it('applies with valid VAT result', function () {
    expect($this->rule->applies(reverseChargeTransaction(), validVatContext()))->toBeTrue();
});

it('does not apply when vatResult is null', function () {
    expect($this->rule->applies(reverseChargeTransaction(), new TaxCalculationContext()))->toBeFalse();
});

it('does not apply when VAT format is invalid', function () {
    $context = new TaxCalculationContext(
        vatResult: VatValidationResultData::invalid('FR', 'INVALID', 'Invalid format', formatValid: false),
    );

    expect($this->rule->applies(reverseChargeTransaction(), $context))->toBeFalse();
});

it('applies on VIES outage with valid format (fail-open)', function () {
    $context = new TaxCalculationContext(
        vatResult: VatValidationResultData::invalid('FR', 'FR12345678901', 'VIES service unavailable'),
    );

    expect($this->rule->applies(reverseChargeTransaction(), $context))->toBeTrue();
});

it('does not apply when VAT is invalid for non-outage reason', function () {
    $context = new TaxCalculationContext(
        vatResult: VatValidationResultData::invalid('FR', 'FR12345678901', 'VAT number not found'),
    );

    expect($this->rule->applies(reverseChargeTransaction(), $context))->toBeFalse();
});

it('does not apply without VAT number', function () {
    $transaction = new TransactionData(
        transactionId: 'txn-rc',
        amount: Money::fromCents(10000),
        sellerCountry: new Country('DE'),
        buyerCountry: new Country('FR'),
        customerType: CustomerType::B2B,
        supplyType: SupplyType::Services,
    );

    expect($this->rule->applies($transaction, validVatContext()))->toBeFalse();
});

it('does not apply to B2C', function () {
    expect($this->rule->applies(
        reverseChargeTransaction(customerType: CustomerType::B2C),
        validVatContext(),
    ))->toBeFalse();
});

it('does not apply to domestic', function () {
    expect($this->rule->applies(
        reverseChargeTransaction(sellerCountry: 'DE', buyerCountry: 'DE'),
        validVatContext(),
    ))->toBeFalse();
});

it('does not apply with empty string VAT number', function () {
    expect($this->rule->applies(
        reverseChargeTransaction(buyerVatNumber: ''),
        validVatContext(),
    ))->toBeFalse();
});

it('evaluates with zero rate and reverse charge', function () {
    $context = validVatContext();
    $decision = $this->rule->evaluate(reverseChargeTransaction(), $context);

    expect($decision->scheme)->toBe(TaxScheme::ReverseCharge)
        ->and($decision->rate)->toBe('0.00')
        ->and($decision->taxCountry->code)->toBe('FR')
        ->and($decision->ruleApplied)->toBe('reverse_charge')
        ->and($decision->reverseCharged)->toBeTrue()
        ->and($decision->vatNumberValidated)->toBeTrue();
});

it('sets vatNumberValidated false on VIES outage', function () {
    $context = new TaxCalculationContext(
        vatResult: VatValidationResultData::invalid('FR', 'FR12345678901', 'VIES service unavailable'),
    );

    $decision = $this->rule->evaluate(reverseChargeTransaction(), $context);

    expect($decision->vatNumberValidated)->toBeFalse()
        ->and($decision->reverseCharged)->toBeTrue();
});

it('has priority 50', function () {
    expect($this->rule->priority())->toBe(50);
});
