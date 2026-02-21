<?php

declare(strict_types=1);

use Veltix\TaxEngine\Data\TaxResultData;
use Veltix\TaxEngine\Data\TransactionData;
use Veltix\TaxEngine\Data\VatValidationResultData;
use Veltix\TaxEngine\Enums\CustomerType;
use Veltix\TaxEngine\Enums\SupplyType;
use Veltix\TaxEngine\Enums\TaxScheme;
use Veltix\TaxEngine\Facades\TaxEngine;
use Veltix\TaxEngine\Support\Country;
use Veltix\TaxEngine\Support\Money;

it('calculates tax via facade', function () {
    $transaction = new TransactionData(
        transactionId: 'facade-001',
        amount: Money::fromCents(10000),
        sellerCountry: new Country('DE'),
        buyerCountry: new Country('DE'),
        customerType: CustomerType::B2C,
        supplyType: SupplyType::Goods,
    );

    $result = TaxEngine::calculate($transaction);

    expect($result)->toBeInstanceOf(TaxResultData::class)
        ->and($result->decision->scheme)->toBe(TaxScheme::Standard)
        ->and($result->decision->rate)->toBe('19.00')
        ->and($result->taxAmount->amount)->toBe(1900)
        ->and($result->grossAmount->amount)->toBe(11900);
});

it('calculates export via facade', function () {
    $transaction = new TransactionData(
        transactionId: 'facade-export-001',
        amount: Money::fromCents(50000),
        sellerCountry: new Country('NL'),
        buyerCountry: new Country('US'),
        customerType: CustomerType::B2B,
        supplyType: SupplyType::Services,
    );

    $result = TaxEngine::calculate($transaction);

    expect($result->decision->scheme)->toBe(TaxScheme::OutsideScope)
        ->and($result->decision->isZeroRated())->toBeTrue()
        ->and($result->taxAmount->amount)->toBe(0);
});

it('calculates reverse charge via facade', function () {
    $transaction = new TransactionData(
        transactionId: 'facade-rc-001',
        amount: Money::fromCents(25000),
        sellerCountry: new Country('DE'),
        buyerCountry: new Country('FR'),
        customerType: CustomerType::B2B,
        supplyType: SupplyType::Services,
        buyerVatNumber: 'FR12345678901',
    );

    $vatResult = VatValidationResultData::validResult('FR', 'FR12345678901');

    $result = TaxEngine::calculate($transaction, $vatResult);

    expect($result->decision->scheme)->toBe(TaxScheme::ReverseCharge)
        ->and($result->decision->reverseCharged)->toBeTrue()
        ->and($result->taxAmount->amount)->toBe(0);
});

it('calculates with reduced rate via facade', function () {
    $transaction = new TransactionData(
        transactionId: 'facade-reduced-001',
        amount: Money::fromCents(10000),
        sellerCountry: new Country('FR'),
        buyerCountry: new Country('FR'),
        customerType: CustomerType::B2C,
        supplyType: SupplyType::Goods,
        metadata: ['rate_category' => 'super_reduced'],
    );

    $result = TaxEngine::calculate($transaction);

    expect($result->decision->rate)->toBe('2.10')
        ->and($result->taxAmount->amount)->toBe(210);
});

it('calculates from array-based transaction via facade', function () {
    $transaction = TransactionData::from([
        'transactionId' => 'facade-from-array',
        'amount' => 20000,
        'sellerCountry' => 'HU',
        'buyerCountry' => 'HU',
        'customerType' => 'b2c',
        'supplyType' => 'goods',
    ]);

    $result = TaxEngine::calculate($transaction);

    expect($result->decision->rate)->toBe('27.00')
        ->and($result->taxAmount->amount)->toBe(5400);
});
