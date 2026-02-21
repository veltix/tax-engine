<?php

declare(strict_types=1);

namespace Veltix\TaxEngine\Contracts;

use Veltix\TaxEngine\Data\ComplianceSnapshotData;

interface ComplianceSnapshotStorageContract
{
    public function store(ComplianceSnapshotData $snapshot): void;

    public function findBySnapshotId(string $snapshotId): ?ComplianceSnapshotData;

    public function findByTransactionId(string $transactionId): ?ComplianceSnapshotData;

    /** @return ComplianceSnapshotData[] */
    public function all(): array;
}
