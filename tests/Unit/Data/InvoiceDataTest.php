<?php

declare(strict_types=1);

use Veltix\TaxEngine\Data\InvoiceData;
use Veltix\TaxEngine\Data\InvoiceLineData;
use Veltix\TaxEngine\Data\TransactionData;
use Veltix\TaxEngine\Enums\CustomerType;
use Veltix\TaxEngine\Enums\SupplyType;
use Veltix\TaxEngine\Support\Country;
use Veltix\TaxEngine\Support\Money;

it('creates InvoiceLineData from array', function () {
    $line = InvoiceLineData::from([
        'lineId' => 'line-1',
        'amount' => Money::fromCents(145),
        'supplyType' => SupplyType::Goods,
        'description' => 'Widget',
        'metadata' => ['sku' => 'W001'],
    ]);

    expect($line->lineId)->toBe('line-1')
        ->and($line->amount->amount)->toBe(145)
        ->and($line->supplyType)->toBe(SupplyType::Goods)
        ->and($line->description)->toBe('Widget')
        ->and($line->metadata)->toBe(['sku' => 'W001']);
});

it('creates InvoiceLineData from array with string supply type', function () {
    $line = InvoiceLineData::from([
        'lineId' => 'line-2',
        'amount' => 500,
        'supplyType' => 'services',
    ]);

    expect($line->lineId)->toBe('line-2')
        ->and($line->amount->amount)->toBe(500)
        ->and($line->supplyType)->toBe(SupplyType::Services);
});

it('creates InvoiceData from array', function () {
    $invoice = InvoiceData::from([
        'invoiceId' => 'inv-001',
        'sellerCountry' => 'NL',
        'buyerCountry' => 'NL',
        'customerType' => 'b2c',
        'lines' => [
            ['lineId' => 'l1', 'amount' => 145, 'supplyType' => 'goods'],
            ['lineId' => 'l2', 'amount' => 200, 'supplyType' => 'goods'],
        ],
        'buyerVatNumber' => null,
        'metadata' => ['source' => 'web'],
    ]);

    expect($invoice->invoiceId)->toBe('inv-001')
        ->and($invoice->sellerCountry->code)->toBe('NL')
        ->and($invoice->buyerCountry->code)->toBe('NL')
        ->and($invoice->customerType)->toBe(CustomerType::B2C)
        ->and($invoice->lines)->toHaveCount(2)
        ->and($invoice->lines[0])->toBeInstanceOf(InvoiceLineData::class)
        ->and($invoice->metadata)->toBe(['source' => 'web']);
});

it('creates InvoiceData from objects', function () {
    $invoice = InvoiceData::from([
        'invoiceId' => 'inv-002',
        'sellerCountry' => new Country('DE'),
        'buyerCountry' => new Country('FR'),
        'customerType' => CustomerType::B2B,
        'lines' => [
            ['lineId' => 'l1', 'amount' => 1000, 'supplyType' => 'services'],
        ],
    ]);

    expect($invoice->sellerCountry->code)->toBe('DE')
        ->and($invoice->buyerCountry->code)->toBe('FR')
        ->and($invoice->customerType)->toBe(CustomerType::B2B);
});

it('bridges to TransactionData via toTransactionData', function () {
    $invoice = new InvoiceData(
        invoiceId: 'inv-100',
        sellerCountry: new Country('NL'),
        buyerCountry: new Country('NL'),
        customerType: CustomerType::B2C,
        lines: [
            new InvoiceLineData(
                lineId: 'line-5',
                amount: Money::fromCents(145),
                supplyType: SupplyType::Goods,
                description: 'Test item',
                metadata: ['sku' => 'T001'],
            ),
        ],
        buyerVatNumber: 'NL123456789B01',
        sellerVatNumber: 'NL987654321B01',
        metadata: ['source' => 'api'],
    );

    $txn = $invoice->toTransactionData($invoice->lines[0]);

    expect($txn)->toBeInstanceOf(TransactionData::class)
        ->and($txn->transactionId)->toBe('inv-100:line-5')
        ->and($txn->amount->amount)->toBe(145)
        ->and($txn->sellerCountry->code)->toBe('NL')
        ->and($txn->buyerCountry->code)->toBe('NL')
        ->and($txn->customerType)->toBe(CustomerType::B2C)
        ->and($txn->supplyType)->toBe(SupplyType::Goods)
        ->and($txn->buyerVatNumber)->toBe('NL123456789B01')
        ->and($txn->sellerVatNumber)->toBe('NL987654321B01')
        ->and($txn->description)->toBe('Test item');
});

it('merges metadata with line winning on key conflicts', function () {
    $invoice = new InvoiceData(
        invoiceId: 'inv-merge',
        sellerCountry: new Country('NL'),
        buyerCountry: new Country('NL'),
        customerType: CustomerType::B2C,
        lines: [
            new InvoiceLineData(
                lineId: 'line-m',
                amount: Money::fromCents(100),
                supplyType: SupplyType::Goods,
                metadata: ['source' => 'line-override', 'sku' => 'ABC'],
            ),
        ],
        metadata: ['source' => 'invoice', 'channel' => 'web'],
    );

    $txn = $invoice->toTransactionData($invoice->lines[0]);

    expect($txn->metadata)->toBe([
        'source' => 'line-override',
        'channel' => 'web',
        'sku' => 'ABC',
    ]);
});
