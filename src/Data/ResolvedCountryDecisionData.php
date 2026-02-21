<?php

declare(strict_types=1);

namespace Veltix\TaxEngine\Data;

use Veltix\TaxEngine\Support\Country;

final readonly class ResolvedCountryDecisionData
{
    public function __construct(
        public Country $resolvedCountry,
        public bool $valid,
        public ?string $failureReason,
        public EvidenceData $supportingEvidence,
        public EvidenceData $conflictingEvidence,
        public string $mode,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'resolvedCountry' => $this->resolvedCountry->code,
            'valid' => $this->valid,
            'failureReason' => $this->failureReason,
            'supportingEvidence' => $this->supportingEvidence->toArray(),
            'conflictingEvidence' => $this->conflictingEvidence->toArray(),
            'mode' => $this->mode,
        ];
    }
}
