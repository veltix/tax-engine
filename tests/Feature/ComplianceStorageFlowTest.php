<?php

declare(strict_types=1);

use Veltix\TaxEngine\Actions\CalculateTaxAction;
use Veltix\TaxEngine\Actions\StoreComplianceSnapshotAction;
use Veltix\TaxEngine\Contracts\ComplianceSnapshotStorageContract;
use Veltix\TaxEngine\Contracts\EvidenceStorageContract;
use Veltix\TaxEngine\Data\TransactionData;
use Veltix\TaxEngine\Data\VatValidationResultData;
use Veltix\TaxEngine\Enums\CustomerType;
use Veltix\TaxEngine\Enums\SupplyType;
use Veltix\TaxEngine\Enums\TaxScheme;
use Veltix\TaxEngine\Support\Country;
use Veltix\TaxEngine\Support\Money;

it('stores compliance snapshot after tax calculation', function () {
    $action = app(CalculateTaxAction::class);
    $snapshotStorage = app(ComplianceSnapshotStorageContract::class);

    $transaction = new TransactionData(
        transactionId: 'compliance-001',
        amount: Money::fromCents(10000),
        sellerCountry: new Country('NL'),
        buyerCountry: new Country('NL'),
        customerType: CustomerType::B2C,
        supplyType: SupplyType::Goods,
    );

    $action->execute($transaction);

    $snapshot = $snapshotStorage->findByTransactionId('compliance-001');

    expect($snapshot)->not->toBeNull()
        ->and($snapshot->transactionId)->toBe('compliance-001')
        ->and($snapshot->decision->scheme)->toBe(TaxScheme::Standard);
});

it('stores evidence after tax calculation keyed by snapshotId', function () {
    $action = app(CalculateTaxAction::class);
    $evidenceStorage = app(EvidenceStorageContract::class);
    $snapshotStorage = app(ComplianceSnapshotStorageContract::class);

    $transaction = new TransactionData(
        transactionId: 'compliance-evidence-001',
        amount: Money::fromCents(10000),
        sellerCountry: new Country('DE'),
        buyerCountry: new Country('DE'),
        customerType: CustomerType::B2C,
        supplyType: SupplyType::Goods,
    );

    $action->execute($transaction);

    $snapshot = $snapshotStorage->findByTransactionId('compliance-evidence-001');
    $evidence = $evidenceStorage->findBySnapshotId($snapshot->snapshotId);

    expect($evidence)->not->toBeNull()
        ->and($evidence->items)->not->toBeEmpty();
});

it('stores multiple transaction snapshots independently', function () {
    $action = app(CalculateTaxAction::class);
    $snapshotStorage = app(ComplianceSnapshotStorageContract::class);

    $action->execute(new TransactionData(
        transactionId: 'multi-001',
        amount: Money::fromCents(10000),
        sellerCountry: new Country('DE'),
        buyerCountry: new Country('DE'),
        customerType: CustomerType::B2C,
        supplyType: SupplyType::Goods,
    ));

    $action->execute(new TransactionData(
        transactionId: 'multi-002',
        amount: Money::fromCents(20000),
        sellerCountry: new Country('NL'),
        buyerCountry: new Country('US'),
        customerType: CustomerType::B2B,
        supplyType: SupplyType::Services,
    ));

    $snapshot1 = $snapshotStorage->findByTransactionId('multi-001');
    $snapshot2 = $snapshotStorage->findByTransactionId('multi-002');

    expect($snapshot1)->not->toBeNull()
        ->and($snapshot1->decision->scheme)->toBe(TaxScheme::Standard)
        ->and($snapshot2)->not->toBeNull()
        ->and($snapshot2->decision->scheme)->toBe(TaxScheme::OutsideScope);
});

it('does not store snapshot when compliance is disabled', function () {
    config()->set('tax.compliance.store_decisions', false);
    config()->set('tax.compliance.store_evidence', false);

    $complianceAction = app(StoreComplianceSnapshotAction::class);
    $snapshotStorage = app(ComplianceSnapshotStorageContract::class);

    $transaction = new TransactionData(
        transactionId: 'no-store-001',
        amount: Money::fromCents(10000),
        sellerCountry: new Country('DE'),
        buyerCountry: new Country('DE'),
        customerType: CustomerType::B2C,
        supplyType: SupplyType::Goods,
    );

    $decision = new \Veltix\TaxEngine\Data\TaxDecisionData(
        scheme: TaxScheme::Standard,
        rate: '19.00',
        taxCountry: new Country('DE'),
        ruleApplied: 'domestic_standard',
        reasoning: 'test',
    );

    $result = $complianceAction->execute($transaction, $decision);

    expect($result)->toBeNull()
        ->and($snapshotStorage->findByTransactionId('no-store-001'))->toBeNull();
});

it('snapshot contains full transaction and decision data', function () {
    $action = app(CalculateTaxAction::class);
    $snapshotStorage = app(ComplianceSnapshotStorageContract::class);

    $vatResult = VatValidationResultData::validResult('DE', 'DE123456789');

    $action->execute(
        transaction: new TransactionData(
            transactionId: 'full-snapshot-001',
            amount: Money::fromCents(50000),
            sellerCountry: new Country('NL'),
            buyerCountry: new Country('DE'),
            customerType: CustomerType::B2B,
            supplyType: SupplyType::Services,
            buyerVatNumber: 'DE123456789',
        ),
        vatResult: $vatResult,
    );

    $snapshot = $snapshotStorage->findByTransactionId('full-snapshot-001');

    expect($snapshot)->not->toBeNull()
        ->and($snapshot->transaction->transactionId)->toBe('full-snapshot-001')
        ->and($snapshot->transaction->sellerCountry->code)->toBe('NL')
        ->and($snapshot->transaction->buyerCountry->code)->toBe('DE')
        ->and($snapshot->decision->scheme)->toBe(TaxScheme::ReverseCharge)
        ->and($snapshot->decision->reverseCharged)->toBeTrue()
        ->and($snapshot->evidence->items)->not->toBeEmpty();
});

it('snapshot serializes to array correctly', function () {
    $action = app(CalculateTaxAction::class);
    $snapshotStorage = app(ComplianceSnapshotStorageContract::class);

    $action->execute(new TransactionData(
        transactionId: 'serialize-001',
        amount: Money::fromCents(10000),
        sellerCountry: new Country('DE'),
        buyerCountry: new Country('DE'),
        customerType: CustomerType::B2C,
        supplyType: SupplyType::Goods,
    ));

    $snapshot = $snapshotStorage->findByTransactionId('serialize-001');
    $array = $snapshot->toArray();

    expect($array)->toHaveKeys([
        'snapshotId', 'transactionId', 'transaction', 'decision',
        'evidence', 'createdAt',
    ])
        ->and($array['transactionId'])->toBe('serialize-001')
        ->and($array['decision']['scheme'])->toBe('standard')
        ->and($array['decision']['rate'])->toBe('19.00');
});
