<?php

declare(strict_types=1);

use Veltix\TaxEngine\Data\ComplianceSnapshotData;
use Veltix\TaxEngine\Data\EvidenceData;
use Veltix\TaxEngine\Data\EvidenceItemData;
use Veltix\TaxEngine\Data\TaxDecisionData;
use Veltix\TaxEngine\Data\TransactionData;
use Veltix\TaxEngine\Enums\CustomerType;
use Veltix\TaxEngine\Enums\SupplyType;
use Veltix\TaxEngine\Enums\TaxScheme;
use Veltix\TaxEngine\Repositories\InMemoryComplianceSnapshotStorage;
use Veltix\TaxEngine\Support\Country;
use Veltix\TaxEngine\Support\Money;

beforeEach(function () {
    $this->storage = new InMemoryComplianceSnapshotStorage();
});

function createSnapshotForStorage(string $transactionId = 'txn-001'): ComplianceSnapshotData
{
    return ComplianceSnapshotData::create(
        transaction: new TransactionData(
            transactionId: $transactionId,
            amount: Money::fromCents(10000),
            sellerCountry: new Country('NL'),
            buyerCountry: new Country('DE'),
            customerType: CustomerType::B2C,
            supplyType: SupplyType::DigitalServices,
        ),
        decision: new TaxDecisionData(
            scheme: TaxScheme::OSS,
            rate: '19.00',
            taxCountry: new Country('DE'),
            ruleApplied: 'oss',
            reasoning: 'Cross-border B2C',
        ),
        evidence: EvidenceData::fromItems(EvidenceItemData::billingAddress('DE')),
    );
}

it('stores and retrieves by snapshot ID', function () {
    $snapshot = createSnapshotForStorage();

    $this->storage->store($snapshot);

    $retrieved = $this->storage->findBySnapshotId($snapshot->snapshotId);

    expect($retrieved)->not->toBeNull()
        ->and($retrieved->snapshotId)->toBe($snapshot->snapshotId);
});

it('stores and retrieves by transaction ID', function () {
    $snapshot = createSnapshotForStorage('txn-002');

    $this->storage->store($snapshot);

    $retrieved = $this->storage->findByTransactionId('txn-002');

    expect($retrieved)->not->toBeNull()
        ->and($retrieved->transactionId)->toBe('txn-002');
});

it('returns null for unknown snapshot ID', function () {
    expect($this->storage->findBySnapshotId('unknown'))->toBeNull();
});

it('returns null for unknown transaction ID', function () {
    expect($this->storage->findByTransactionId('unknown'))->toBeNull();
});

it('returns all stored snapshots', function () {
    $this->storage->store(createSnapshotForStorage('txn-001'));
    $this->storage->store(createSnapshotForStorage('txn-002'));

    expect($this->storage->all())->toHaveCount(2);
});

it('returns empty array when no snapshots stored', function () {
    expect($this->storage->all())->toBe([]);
});
