<?php

declare(strict_types=1);

use Veltix\TaxEngine\Data\EvidenceData;
use Veltix\TaxEngine\Data\EvidenceItemData;
use Veltix\TaxEngine\Repositories\InMemoryEvidenceStorage;

beforeEach(function () {
    $this->storage = new InMemoryEvidenceStorage();
});

it('stores and retrieves evidence by transaction ID', function () {
    $evidence = EvidenceData::fromItems(EvidenceItemData::billingAddress('DE'));

    $this->storage->store('txn-001', $evidence);

    $retrieved = $this->storage->findBySnapshotId('txn-001');

    expect($retrieved)->not->toBeNull()
        ->and($retrieved->count())->toBe(1)
        ->and($retrieved->items[0]->resolvedCountryCode)->toBe('DE');
});

it('returns null for unknown transaction ID', function () {
    expect($this->storage->findBySnapshotId('unknown'))->toBeNull();
});

it('overwrites evidence for same transaction ID', function () {
    $first = EvidenceData::fromItems(EvidenceItemData::billingAddress('DE'));
    $second = EvidenceData::fromItems(
        EvidenceItemData::billingAddress('FR'),
        EvidenceItemData::ipAddress('FR'),
    );

    $this->storage->store('txn-001', $first);
    $this->storage->store('txn-001', $second);

    $retrieved = $this->storage->findBySnapshotId('txn-001');

    expect($retrieved->count())->toBe(2)
        ->and($retrieved->items[0]->resolvedCountryCode)->toBe('FR');
});
