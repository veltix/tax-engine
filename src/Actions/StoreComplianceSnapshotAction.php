<?php

declare(strict_types=1);

namespace Veltix\TaxEngine\Actions;

use Illuminate\Support\Facades\DB;
use Veltix\TaxEngine\Contracts\ComplianceSnapshotStorageContract;
use Veltix\TaxEngine\Contracts\EvidenceStorageContract;
use Veltix\TaxEngine\Data\ComplianceSnapshotData;
use Veltix\TaxEngine\Data\ResolvedLocationData;
use Veltix\TaxEngine\Data\TaxDecisionData;
use Veltix\TaxEngine\Data\TransactionData;
use Veltix\TaxEngine\Data\VatValidationResultData;
use Veltix\TaxEngine\Services\EvidenceCollectorService;
use Veltix\TaxEngine\Support\VersionInfo;

final class StoreComplianceSnapshotAction
{
    public function __construct(
        private readonly EvidenceCollectorService $collector,
        private readonly ComplianceSnapshotStorageContract $snapshotStorage,
        private readonly EvidenceStorageContract $evidenceStorage,
        private readonly bool $storeDecisions,
        private readonly bool $storeEvidence,
    ) {}

    /**
     * @param array<string, mixed> $metadata
     */
    public function execute(
        TransactionData $transaction,
        TaxDecisionData $decision,
        ?VatValidationResultData $vatResult = null,
        ?ResolvedLocationData $resolvedLocation = null,
        array $metadata = [],
    ): ?ComplianceSnapshotData {
        if (! $this->storeDecisions && ! $this->storeEvidence) {
            return null;
        }

        $evidence = $this->collector->collect($transaction, $vatResult);

        if (! $this->storeDecisions) {
            // Cannot store evidence without a parent snapshot (FK constraint)
            return null;
        }

        $snapshot = ComplianceSnapshotData::create(
            transaction: $transaction,
            decision: $decision,
            evidence: $evidence,
            resolvedLocation: $resolvedLocation,
            ruleVersion: VersionInfo::ruleVersion(),
            rateDatasetVersion: VersionInfo::rateDatasetVersion(),
            policyHash: VersionInfo::policyHash(),
            metadata: $metadata,
        );

        DB::transaction(function () use ($snapshot, $evidence): void {
            $this->snapshotStorage->store($snapshot);

            if ($this->storeEvidence) {
                $this->evidenceStorage->store($snapshot->snapshotId, $evidence);
            }
        });

        return $snapshot;
    }
}
