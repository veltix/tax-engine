<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Veltix\TaxEngine\Data\ComplianceSnapshotData;
use Veltix\TaxEngine\Data\EvidenceData;
use Veltix\TaxEngine\Data\EvidenceItemData;
use Veltix\TaxEngine\Data\ResolvedLocationData;
use Veltix\TaxEngine\Data\TaxDecisionData;
use Veltix\TaxEngine\Data\TransactionData;
use Veltix\TaxEngine\Enums\CustomerType;
use Veltix\TaxEngine\Enums\SupplyType;
use Veltix\TaxEngine\Enums\TaxScheme;
use Veltix\TaxEngine\Repositories\DatabaseComplianceSnapshotStorage;
use Veltix\TaxEngine\Support\Country;
use Veltix\TaxEngine\Support\Money;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->repo = new DatabaseComplianceSnapshotStorage();
});

function makeDbSnapshot(string $txnId = 'db-txn-001', ?ResolvedLocationData $location = null): ComplianceSnapshotData
{
    $transaction = new TransactionData(
        transactionId: $txnId,
        amount: Money::fromCents(10000),
        sellerCountry: new Country('NL'),
        buyerCountry: new Country('DE'),
        customerType: CustomerType::B2C,
        supplyType: SupplyType::DigitalServices,
    );

    $decision = new TaxDecisionData(
        scheme: TaxScheme::OSS,
        rate: '19.00',
        taxCountry: new Country('DE'),
        ruleApplied: 'oss',
        reasoning: 'Cross-border B2C digital service',
        vatNumberValidated: false,
        reverseCharged: false,
    );

    $evidence = EvidenceData::fromItems(
        EvidenceItemData::billingAddress('DE'),
        EvidenceItemData::ipAddress('DE'),
    );

    return ComplianceSnapshotData::create(
        transaction: $transaction,
        decision: $decision,
        evidence: $evidence,
        resolvedLocation: $location,
        ruleVersion: '1.0.0',
        rateDatasetVersion: '2024.1',
        metadata: ['source' => 'test'],
    );
}

it('stores and retrieves snapshot by snapshot ID', function () {
    $snapshot = makeDbSnapshot();

    $this->repo->store($snapshot);

    $found = $this->repo->findBySnapshotId($snapshot->snapshotId);

    expect($found)->not->toBeNull()
        ->and($found->snapshotId)->toBe($snapshot->snapshotId)
        ->and($found->transactionId)->toBe('db-txn-001')
        ->and($found->decision->scheme)->toBe(TaxScheme::OSS)
        ->and($found->decision->rate)->toBe('19.00')
        ->and($found->decision->taxCountry->code)->toBe('DE')
        ->and($found->decision->ruleApplied)->toBe('oss')
        ->and($found->ruleVersion)->toBe('1.0.0')
        ->and($found->rateDatasetVersion)->toBe('2024.1');
});

it('stores and retrieves snapshot by transaction ID', function () {
    $snapshot = makeDbSnapshot('db-txn-002');

    $this->repo->store($snapshot);

    $found = $this->repo->findByTransactionId('db-txn-002');

    expect($found)->not->toBeNull()
        ->and($found->transactionId)->toBe('db-txn-002')
        ->and($found->transaction->sellerCountry->code)->toBe('NL')
        ->and($found->transaction->buyerCountry->code)->toBe('DE');
});

it('returns null for unknown snapshot ID', function () {
    expect($this->repo->findBySnapshotId('nonexistent'))->toBeNull();
});

it('returns null for unknown transaction ID', function () {
    expect($this->repo->findByTransactionId('nonexistent'))->toBeNull();
});

it('returns all stored snapshots', function () {
    $this->repo->store(makeDbSnapshot('db-all-001'));
    $this->repo->store(makeDbSnapshot('db-all-002'));
    $this->repo->store(makeDbSnapshot('db-all-003'));

    $all = $this->repo->all();

    expect($all)->toHaveCount(3);
});

it('hydrates evidence correctly', function () {
    $snapshot = makeDbSnapshot('db-evidence-001');

    $this->repo->store($snapshot);

    $found = $this->repo->findBySnapshotId($snapshot->snapshotId);

    expect($found->evidence->items)->toHaveCount(2)
        ->and($found->evidence->items[0]->resolvedCountryCode)->toBe('DE')
        ->and($found->evidence->items[1]->resolvedCountryCode)->toBe('DE');
});

it('hydrates transaction data correctly', function () {
    $snapshot = makeDbSnapshot('db-hydrate-001');

    $this->repo->store($snapshot);

    $found = $this->repo->findBySnapshotId($snapshot->snapshotId);

    expect($found->transaction->transactionId)->toBe('db-hydrate-001')
        ->and($found->transaction->amount->amount)->toBe(10000)
        ->and($found->transaction->customerType)->toBe(CustomerType::B2C)
        ->and($found->transaction->supplyType)->toBe(SupplyType::DigitalServices);
});

it('stores and retrieves snapshot with resolved location', function () {
    $location = new ResolvedLocationData(
        resolvedCountry: new Country('DE'),
        evidenceUsed: EvidenceData::fromItems(
            EvidenceItemData::billingAddress('DE'),
            EvidenceItemData::ipAddress('DE'),
        ),
        evidenceIgnored: EvidenceData::empty(),
        confidenceLevel: 'high',
        requiresManualReview: false,
        summary: 'Resolved to DE with high confidence',
    );

    $snapshot = makeDbSnapshot('db-location-001', $location);

    $this->repo->store($snapshot);

    $found = $this->repo->findBySnapshotId($snapshot->snapshotId);

    expect($found->resolvedLocation)->not->toBeNull()
        ->and($found->resolvedLocation->resolvedCountry->code)->toBe('DE')
        ->and($found->resolvedLocation->confidenceLevel)->toBe('high')
        ->and($found->resolvedLocation->requiresManualReview)->toBeFalse()
        ->and($found->resolvedLocation->evidenceUsed->items)->toHaveCount(2);
});

it('stores snapshot without resolved location', function () {
    $snapshot = makeDbSnapshot('db-no-location-001');

    $this->repo->store($snapshot);

    $found = $this->repo->findBySnapshotId($snapshot->snapshotId);

    expect($found->resolvedLocation)->toBeNull();
});

it('preserves metadata through storage roundtrip', function () {
    $snapshot = makeDbSnapshot('db-metadata-001');

    $this->repo->store($snapshot);

    $found = $this->repo->findBySnapshotId($snapshot->snapshotId);

    expect($found->metadata)->toBe(['source' => 'test']);
});
