<?php

declare(strict_types=1);

namespace Veltix\TaxEngine\Repositories;

use Veltix\TaxEngine\Contracts\EvidenceStorageContract;
use Veltix\TaxEngine\Data\EvidenceData;

final class InMemoryEvidenceStorage implements EvidenceStorageContract
{
    /** @var array<string, EvidenceData> */
    private array $storage = [];

    public function store(string $snapshotId, EvidenceData $evidence): void
    {
        $this->storage[$snapshotId] = $evidence;
    }

    public function findBySnapshotId(string $snapshotId): ?EvidenceData
    {
        return $this->storage[$snapshotId] ?? null;
    }
}
