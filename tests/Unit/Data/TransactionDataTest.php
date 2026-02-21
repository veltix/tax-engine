<?php

declare(strict_types=1);

use Veltix\TaxEngine\Data\TransactionData;
use Veltix\TaxEngine\Enums\CustomerType;
use Veltix\TaxEngine\Enums\SupplyType;
use Veltix\TaxEngine\Support\Country;
use Veltix\TaxEngine\Support\Money;

it('constructs with all properties', function () {
    $transaction = new TransactionData(
        transactionId: 'txn-001',
        amount: Money::fromCents(10000),
        sellerCountry: new Country('NL'),
        buyerCountry: new Country('DE'),
        customerType: CustomerType::B2B,
        supplyType: SupplyType::Services,
        buyerVatNumber: 'DE123456789',
        sellerVatNumber: 'NL123456789B01',
        date: new DateTimeImmutable('2025-01-15'),
        description: 'Consulting services',
        metadata: ['key' => 'value'],
    );

    expect($transaction->transactionId)->toBe('txn-001')
        ->and($transaction->amount->amount)->toBe(10000)
        ->and($transaction->sellerCountry->code)->toBe('NL')
        ->and($transaction->buyerCountry->code)->toBe('DE')
        ->and($transaction->customerType)->toBe(CustomerType::B2B)
        ->and($transaction->supplyType)->toBe(SupplyType::Services)
        ->and($transaction->buyerVatNumber)->toBe('DE123456789')
        ->and($transaction->sellerVatNumber)->toBe('NL123456789B01')
        ->and($transaction->description)->toBe('Consulting services')
        ->and($transaction->metadata)->toBe(['key' => 'value']);
});

it('constructs with defaults', function () {
    $transaction = new TransactionData(
        transactionId: 'txn-002',
        amount: Money::fromCents(5000),
        sellerCountry: new Country('NL'),
        buyerCountry: new Country('NL'),
        customerType: CustomerType::B2C,
        supplyType: SupplyType::Goods,
    );

    expect($transaction->buyerVatNumber)->toBeNull()
        ->and($transaction->sellerVatNumber)->toBeNull()
        ->and($transaction->date)->toBeNull()
        ->and($transaction->description)->toBeNull()
        ->and($transaction->metadata)->toBe([]);
});

it('creates from array with objects', function () {
    $transaction = TransactionData::from([
        'transactionId' => 'txn-003',
        'amount' => Money::fromCents(2000),
        'sellerCountry' => new Country('NL'),
        'buyerCountry' => new Country('FR'),
        'customerType' => CustomerType::B2C,
        'supplyType' => SupplyType::DigitalServices,
    ]);

    expect($transaction->transactionId)->toBe('txn-003')
        ->and($transaction->amount->amount)->toBe(2000);
});

it('creates from array with primitives', function () {
    $transaction = TransactionData::from([
        'transactionId' => 'txn-004',
        'amount' => 3000,
        'sellerCountry' => 'NL',
        'buyerCountry' => 'DE',
        'customerType' => 'b2b',
        'supplyType' => 'goods',
        'buyerVatNumber' => 'DE999999999',
        'date' => '2025-06-01',
    ]);

    expect($transaction->transactionId)->toBe('txn-004')
        ->and($transaction->amount->amount)->toBe(3000)
        ->and($transaction->sellerCountry->code)->toBe('NL')
        ->and($transaction->buyerCountry->code)->toBe('DE')
        ->and($transaction->customerType)->toBe(CustomerType::B2B)
        ->and($transaction->supplyType)->toBe(SupplyType::Goods)
        ->and($transaction->buyerVatNumber)->toBe('DE999999999');
});

it('returns isB2B for B2B customer type', function () {
    $transaction = makeTransaction(customerType: CustomerType::B2B);

    expect($transaction->isB2B())->toBeTrue()
        ->and($transaction->isB2C())->toBeFalse();
});

it('returns isB2C for B2C customer type', function () {
    $transaction = makeTransaction(customerType: CustomerType::B2C);

    expect($transaction->isB2C())->toBeTrue()
        ->and($transaction->isB2B())->toBeFalse();
});

it('detects domestic when same country', function () {
    $transaction = makeTransaction(sellerCountry: 'NL', buyerCountry: 'NL');

    expect($transaction->isDomestic())->toBeTrue()
        ->and($transaction->isCrossBorderEu())->toBeFalse();
});

it('detects cross-border EU when different EU countries', function () {
    $transaction = makeTransaction(sellerCountry: 'NL', buyerCountry: 'DE');

    expect($transaction->isCrossBorderEu())->toBeTrue()
        ->and($transaction->isDomestic())->toBeFalse();
});

it('detects export when EU to non-EU', function () {
    $transaction = makeTransaction(sellerCountry: 'NL', buyerCountry: 'US');

    expect($transaction->isExport())->toBeTrue()
        ->and($transaction->isCrossBorderEu())->toBeFalse();
});

it('detects digital services', function () {
    expect(makeTransaction(supplyType: SupplyType::DigitalServices)->isDigitalService())->toBeTrue()
        ->and(makeTransaction(supplyType: SupplyType::Telecommunications)->isDigitalService())->toBeTrue()
        ->and(makeTransaction(supplyType: SupplyType::Broadcasting)->isDigitalService())->toBeTrue()
        ->and(makeTransaction(supplyType: SupplyType::Goods)->isDigitalService())->toBeFalse();
});

it('serializes to array', function () {
    $transaction = new TransactionData(
        transactionId: 'txn-round',
        amount: Money::fromCents(5000),
        sellerCountry: new Country('NL'),
        buyerCountry: new Country('DE'),
        customerType: CustomerType::B2B,
        supplyType: SupplyType::Services,
        buyerVatNumber: 'DE123456789',
    );

    $array = $transaction->toArray();

    expect($array['transactionId'])->toBe('txn-round')
        ->and($array['sellerCountry'])->toBe('NL')
        ->and($array['buyerCountry'])->toBe('DE')
        ->and($array['customerType'])->toBe('b2b')
        ->and($array['supplyType'])->toBe('services')
        ->and($array['buyerVatNumber'])->toBe('DE123456789');
});

function makeTransaction(
    string $sellerCountry = 'NL',
    string $buyerCountry = 'DE',
    CustomerType $customerType = CustomerType::B2B,
    SupplyType $supplyType = SupplyType::Services,
): TransactionData {
    return new TransactionData(
        transactionId: 'txn-test',
        amount: Money::fromCents(10000),
        sellerCountry: new Country($sellerCountry),
        buyerCountry: new Country($buyerCountry),
        customerType: $customerType,
        supplyType: $supplyType,
    );
}
