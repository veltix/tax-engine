<?php

declare(strict_types=1);

use Veltix\TaxEngine\Actions\CalculateTaxAction;
use Veltix\TaxEngine\Data\TransactionData;
use Veltix\TaxEngine\Data\VatValidationResultData;
use Veltix\TaxEngine\Enums\CustomerType;
use Veltix\TaxEngine\Enums\SupplyType;
use Veltix\TaxEngine\Enums\TaxScheme;
use Veltix\TaxEngine\Support\Country;
use Veltix\TaxEngine\Support\Money;

it('calculates domestic B2C standard VAT end-to-end', function () {
    $action = app(CalculateTaxAction::class);

    $result = $action->execute(new TransactionData(
        transactionId: 'flow-domestic-001',
        amount: Money::fromCents(10000),
        sellerCountry: new Country('DE'),
        buyerCountry: new Country('DE'),
        customerType: CustomerType::B2C,
        supplyType: SupplyType::Goods,
    ));

    expect($result->decision->scheme)->toBe(TaxScheme::Standard)
        ->and($result->decision->rate)->toBe('19.00')
        ->and($result->decision->taxCountry->code)->toBe('DE')
        ->and($result->taxAmount->amount)->toBe(1900)
        ->and($result->grossAmount->amount)->toBe(11900)
        ->and($result->transactionId)->toBe('flow-domestic-001');
});

it('calculates domestic B2B standard VAT end-to-end', function () {
    $action = app(CalculateTaxAction::class);

    $result = $action->execute(new TransactionData(
        transactionId: 'flow-domestic-b2b-001',
        amount: Money::fromCents(50000),
        sellerCountry: new Country('FR'),
        buyerCountry: new Country('FR'),
        customerType: CustomerType::B2B,
        supplyType: SupplyType::Services,
    ));

    expect($result->decision->scheme)->toBe(TaxScheme::Standard)
        ->and($result->decision->rate)->toBe('20.00')
        ->and($result->taxAmount->amount)->toBe(10000)
        ->and($result->grossAmount->amount)->toBe(60000);
});

it('calculates reverse charge for cross-border B2B with VAT', function () {
    $action = app(CalculateTaxAction::class);

    $vatResult = VatValidationResultData::validResult('DE', 'DE123456789');

    $result = $action->execute(
        transaction: new TransactionData(
            transactionId: 'flow-rc-001',
            amount: Money::fromCents(25000),
            sellerCountry: new Country('NL'),
            buyerCountry: new Country('DE'),
            customerType: CustomerType::B2B,
            supplyType: SupplyType::Services,
            buyerVatNumber: 'DE123456789',
        ),
        vatResult: $vatResult,
    );

    expect($result->decision->scheme)->toBe(TaxScheme::ReverseCharge)
        ->and($result->decision->rate)->toBe('0.00')
        ->and($result->decision->reverseCharged)->toBeTrue()
        ->and($result->decision->vatNumberValidated)->toBeTrue()
        ->and($result->decision->taxCountry->code)->toBe('DE')
        ->and($result->taxAmount->amount)->toBe(0)
        ->and($result->grossAmount->amount)->toBe(25000);
});

it('calculates export zero-rate for EU to non-EU', function () {
    $action = app(CalculateTaxAction::class);

    $result = $action->execute(new TransactionData(
        transactionId: 'flow-export-001',
        amount: Money::fromCents(100000),
        sellerCountry: new Country('NL'),
        buyerCountry: new Country('US'),
        customerType: CustomerType::B2B,
        supplyType: SupplyType::Goods,
    ));

    expect($result->decision->scheme)->toBe(TaxScheme::Export)
        ->and($result->decision->rate)->toBe('0.00')
        ->and($result->decision->isZeroRated())->toBeTrue()
        ->and($result->taxAmount->amount)->toBe(0)
        ->and($result->grossAmount->amount)->toBe(100000);
});

it('calculates export for B2C to non-EU', function () {
    $action = app(CalculateTaxAction::class);

    $result = $action->execute(new TransactionData(
        transactionId: 'flow-export-b2c-001',
        amount: Money::fromCents(5000),
        sellerCountry: new Country('DE'),
        buyerCountry: new Country('JP'),
        customerType: CustomerType::B2C,
        supplyType: SupplyType::DigitalServices,
    ));

    expect($result->decision->scheme)->toBe(TaxScheme::OutsideScope)
        ->and($result->decision->rate)->toBe('0.00');
});

it('calculates domestic with reduced rate category', function () {
    $action = app(CalculateTaxAction::class);

    $result = $action->execute(new TransactionData(
        transactionId: 'flow-reduced-001',
        amount: Money::fromCents(10000),
        sellerCountry: new Country('DE'),
        buyerCountry: new Country('DE'),
        customerType: CustomerType::B2C,
        supplyType: SupplyType::Goods,
        metadata: ['rate_category' => 'reduced'],
    ));

    expect($result->decision->rate)->toBe('7.00')
        ->and($result->taxAmount->amount)->toBe(700)
        ->and($result->grossAmount->amount)->toBe(10700);
});

it('calculates domestic reverse charge with metadata flag', function () {
    $action = app(CalculateTaxAction::class);

    $result = $action->execute(new TransactionData(
        transactionId: 'flow-drc-001',
        amount: Money::fromCents(80000),
        sellerCountry: new Country('NL'),
        buyerCountry: new Country('NL'),
        customerType: CustomerType::B2B,
        supplyType: SupplyType::Services,
        metadata: ['domestic_reverse_charge' => true],
    ));

    expect($result->decision->scheme)->toBe(TaxScheme::DomesticReverseCharge)
        ->and($result->decision->reverseCharged)->toBeTrue()
        ->and($result->decision->rate)->toBe('0.00')
        ->and($result->taxAmount->amount)->toBe(0);
});

it('calculates correct effective rate across different countries', function (string $country, string $expectedRate, int $expectedTax) {
    $action = app(CalculateTaxAction::class);

    $result = $action->execute(new TransactionData(
        transactionId: "flow-country-{$country}",
        amount: Money::fromCents(10000),
        sellerCountry: new Country($country),
        buyerCountry: new Country($country),
        customerType: CustomerType::B2C,
        supplyType: SupplyType::Goods,
    ));

    expect($result->decision->rate)->toBe($expectedRate)
        ->and($result->taxAmount->amount)->toBe($expectedTax)
        ->and($result->effectiveRate())->toBe($expectedRate);
})->with([
    ['DE', '19.00', 1900],
    ['FR', '20.00', 2000],
    ['NL', '21.00', 2100],
    ['HU', '27.00', 2700],
    ['LU', '17.00', 1700],
    ['SE', '25.00', 2500],
    ['FI', '25.50', 2550],
]);

it('uses TransactionData::from for array-based input', function () {
    $action = app(CalculateTaxAction::class);

    $transaction = TransactionData::from([
        'transactionId' => 'flow-from-array',
        'amount' => 10000,
        'sellerCountry' => 'DE',
        'buyerCountry' => 'DE',
        'customerType' => 'b2c',
        'supplyType' => 'goods',
    ]);

    $result = $action->execute($transaction);

    expect($result->decision->scheme)->toBe(TaxScheme::Standard)
        ->and($result->decision->rate)->toBe('19.00');
});
