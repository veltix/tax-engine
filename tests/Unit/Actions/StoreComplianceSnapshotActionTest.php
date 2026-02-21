<?php

declare(strict_types=1);

use Veltix\TaxEngine\Actions\StoreComplianceSnapshotAction;
use Veltix\TaxEngine\Data\ComplianceSnapshotData;
use Veltix\TaxEngine\Data\TaxDecisionData;
use Veltix\TaxEngine\Data\TransactionData;
use Veltix\TaxEngine\Enums\CustomerType;
use Veltix\TaxEngine\Enums\SupplyType;
use Veltix\TaxEngine\Enums\TaxScheme;
use Veltix\TaxEngine\Repositories\InMemoryComplianceSnapshotStorage;
use Veltix\TaxEngine\Repositories\InMemoryEvidenceStorage;
use Veltix\TaxEngine\Services\EvidenceCollectorService;
use Veltix\TaxEngine\Support\Country;
use Veltix\TaxEngine\Support\Money;

function makeActionTransaction(): TransactionData
{
    return new TransactionData(
        transactionId: 'txn-action-001',
        amount: Money::fromCents(10000),
        sellerCountry: new Country('NL'),
        buyerCountry: new Country('DE'),
        customerType: CustomerType::B2C,
        supplyType: SupplyType::DigitalServices,
    );
}

function makeActionDecision(): TaxDecisionData
{
    return new TaxDecisionData(
        scheme: TaxScheme::OSS,
        rate: '19.00',
        taxCountry: new Country('DE'),
        ruleApplied: 'oss',
        reasoning: 'Cross-border B2C',
    );
}

it('can be resolved from the container', function () {
    $action = app(StoreComplianceSnapshotAction::class);

    expect($action)->toBeInstanceOf(StoreComplianceSnapshotAction::class);
});

it('stores snapshot and evidence when both configured', function () {
    $snapshotStorage = new InMemoryComplianceSnapshotStorage();
    $evidenceStorage = new InMemoryEvidenceStorage();

    $action = new StoreComplianceSnapshotAction(
        collector: new EvidenceCollectorService(),
        snapshotStorage: $snapshotStorage,
        evidenceStorage: $evidenceStorage,
        storeDecisions: true,
        storeEvidence: true,
    );

    $result = $action->execute(makeActionTransaction(), makeActionDecision());

    expect($result)->toBeInstanceOf(ComplianceSnapshotData::class)
        ->and($snapshotStorage->findBySnapshotId($result->snapshotId))->not->toBeNull()
        ->and($evidenceStorage->findBySnapshotId($result->snapshotId))->not->toBeNull();
});

it('stores evidence with snapshotId key not transactionId', function () {
    $snapshotStorage = new InMemoryComplianceSnapshotStorage();
    $evidenceStorage = new InMemoryEvidenceStorage();

    $action = new StoreComplianceSnapshotAction(
        collector: new EvidenceCollectorService(),
        snapshotStorage: $snapshotStorage,
        evidenceStorage: $evidenceStorage,
        storeDecisions: true,
        storeEvidence: true,
    );

    $result = $action->execute(makeActionTransaction(), makeActionDecision());

    // Evidence should be keyed by snapshotId, not transactionId
    expect($evidenceStorage->findBySnapshotId($result->snapshotId))->not->toBeNull()
        ->and($evidenceStorage->findBySnapshotId('txn-action-001'))->toBeNull();
});

it('returns null when both store flags are disabled', function () {
    $action = new StoreComplianceSnapshotAction(
        collector: new EvidenceCollectorService(),
        snapshotStorage: new InMemoryComplianceSnapshotStorage(),
        evidenceStorage: new InMemoryEvidenceStorage(),
        storeDecisions: false,
        storeEvidence: false,
    );

    $result = $action->execute(makeActionTransaction(), makeActionDecision());

    expect($result)->toBeNull();
});

it('does not store evidence when storeDecisions is false', function () {
    $snapshotStorage = new InMemoryComplianceSnapshotStorage();
    $evidenceStorage = new InMemoryEvidenceStorage();

    $action = new StoreComplianceSnapshotAction(
        collector: new EvidenceCollectorService(),
        snapshotStorage: $snapshotStorage,
        evidenceStorage: $evidenceStorage,
        storeDecisions: false,
        storeEvidence: true,
    );

    $result = $action->execute(makeActionTransaction(), makeActionDecision());

    expect($result)->toBeNull()
        ->and($snapshotStorage->all())->toHaveCount(0);
});

it('creates snapshot before storing evidence', function () {
    $snapshotStorage = new InMemoryComplianceSnapshotStorage();
    $evidenceStorage = new InMemoryEvidenceStorage();

    $action = new StoreComplianceSnapshotAction(
        collector: new EvidenceCollectorService(),
        snapshotStorage: $snapshotStorage,
        evidenceStorage: $evidenceStorage,
        storeDecisions: true,
        storeEvidence: true,
    );

    $result = $action->execute(makeActionTransaction(), makeActionDecision());

    // Snapshot exists (was created first)
    expect($snapshotStorage->findBySnapshotId($result->snapshotId))->not->toBeNull()
        // Evidence stored with snapshotId (which references the parent)
        ->and($evidenceStorage->findBySnapshotId($result->snapshotId))->not->toBeNull();
});
