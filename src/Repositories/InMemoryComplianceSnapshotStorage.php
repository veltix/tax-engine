<?php

declare(strict_types=1);

namespace Veltix\TaxEngine\Repositories;

use Veltix\TaxEngine\Contracts\ComplianceSnapshotStorageContract;
use Veltix\TaxEngine\Data\ComplianceSnapshotData;

final class InMemoryComplianceSnapshotStorage implements ComplianceSnapshotStorageContract
{
    /** @var array<string, ComplianceSnapshotData> */
    private array $bySnapshotId = [];

    /** @var array<string, ComplianceSnapshotData> */
    private array $byTransactionId = [];

    public function store(ComplianceSnapshotData $snapshot): void
    {
        $this->bySnapshotId[$snapshot->snapshotId] = $snapshot;
        $this->byTransactionId[$snapshot->transactionId] = $snapshot;
    }

    public function findBySnapshotId(string $snapshotId): ?ComplianceSnapshotData
    {
        return $this->bySnapshotId[$snapshotId] ?? null;
    }

    public function findByTransactionId(string $transactionId): ?ComplianceSnapshotData
    {
        return $this->byTransactionId[$transactionId] ?? null;
    }

    /** @return ComplianceSnapshotData[] */
    public function all(): array
    {
        return array_values($this->bySnapshotId);
    }
}
