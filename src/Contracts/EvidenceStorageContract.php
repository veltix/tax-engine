<?php

declare(strict_types=1);

namespace Veltix\TaxEngine\Contracts;

use Veltix\TaxEngine\Data\EvidenceData;

interface EvidenceStorageContract
{
    public function store(string $snapshotId, EvidenceData $evidence): void;

    public function findBySnapshotId(string $snapshotId): ?EvidenceData;
}
