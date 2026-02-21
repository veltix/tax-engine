<?php

declare(strict_types=1);

namespace Veltix\TaxEngine\Data;

use DateTimeImmutable;

final readonly class ComplianceSnapshotData
{
    public function __construct(
        public string $snapshotId,
        public string $transactionId,
        public TransactionData $transaction,
        public TaxDecisionData $decision,
        public EvidenceData $evidence,
        public ?ResolvedLocationData $resolvedLocation,
        public ?string $ruleVersion,
        public ?string $rateDatasetVersion,
        public ?string $policyHash,
        public DateTimeImmutable $createdAt,
        /** @var array<string, mixed> */
        public array $metadata = [],
    ) {}

    /**
     * @param array<string, mixed> $metadata
     */
    public static function create(
        TransactionData $transaction,
        TaxDecisionData $decision,
        EvidenceData $evidence,
        ?ResolvedLocationData $resolvedLocation = null,
        ?string $ruleVersion = null,
        ?string $rateDatasetVersion = null,
        ?string $policyHash = null,
        array $metadata = [],
    ): self {
        return new self(
            snapshotId: bin2hex(random_bytes(16)),
            transactionId: $transaction->transactionId,
            transaction: $transaction,
            decision: $decision,
            evidence: $evidence,
            resolvedLocation: $resolvedLocation,
            ruleVersion: $ruleVersion,
            rateDatasetVersion: $rateDatasetVersion,
            policyHash: $policyHash,
            createdAt: new DateTimeImmutable(),
            metadata: $metadata,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'snapshotId' => $this->snapshotId,
            'transactionId' => $this->transactionId,
            'transaction' => $this->transaction->toArray(),
            'decision' => $this->decision->toArray(),
            'evidence' => $this->evidence->toArray(),
            'resolvedLocation' => $this->resolvedLocation?->toArray(),
            'ruleVersion' => $this->ruleVersion,
            'rateDatasetVersion' => $this->rateDatasetVersion,
            'policyHash' => $this->policyHash,
            'createdAt' => $this->createdAt->format('c'),
            'metadata' => $this->metadata,
        ];
    }
}
