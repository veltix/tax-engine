<?php

declare(strict_types=1);

use Veltix\TaxEngine\Actions\CalculateTaxAction;
use Veltix\TaxEngine\Contracts\ComplianceSnapshotStorageContract;
use Veltix\TaxEngine\Data\TransactionData;
use Veltix\TaxEngine\Enums\CustomerType;
use Veltix\TaxEngine\Enums\SupplyType;
use Veltix\TaxEngine\Enums\TaxScheme;
use Veltix\TaxEngine\Support\Country;
use Veltix\TaxEngine\Support\Money;

it('stores same transactionId twice as separate snapshots and retrieves latest', function () {
    $action = app(CalculateTaxAction::class);
    $snapshotStorage = app(ComplianceSnapshotStorageContract::class);

    $transaction1 = new TransactionData(
        transactionId: 'txn-idem-001',
        amount: Money::fromCents(10000),
        sellerCountry: new Country('DE'),
        buyerCountry: new Country('DE'),
        customerType: CustomerType::B2C,
        supplyType: SupplyType::Goods,
    );

    $transaction2 = new TransactionData(
        transactionId: 'txn-idem-001',
        amount: Money::fromCents(20000),
        sellerCountry: new Country('NL'),
        buyerCountry: new Country('NL'),
        customerType: CustomerType::B2C,
        supplyType: SupplyType::Goods,
    );

    $action->execute($transaction1);
    $action->execute($transaction2);

    $snapshot = $snapshotStorage->findByTransactionId('txn-idem-001');

    expect($snapshot)->not->toBeNull()
        ->and($snapshot->decision->scheme)->toBe(TaxScheme::Standard);
});
