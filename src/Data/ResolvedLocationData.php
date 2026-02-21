<?php

declare(strict_types=1);

namespace Veltix\TaxEngine\Data;

use Veltix\TaxEngine\Support\Country;

final readonly class ResolvedLocationData
{
    public function __construct(
        public Country $resolvedCountry,
        public EvidenceData $evidenceUsed,
        public EvidenceData $evidenceIgnored,
        public string $confidenceLevel,
        public bool $requiresManualReview,
        public ?string $summary = null,
    ) {}

    public static function fromBuyerCountry(Country $country): self
    {
        return new self(
            resolvedCountry: $country,
            evidenceUsed: EvidenceData::empty(),
            evidenceIgnored: EvidenceData::empty(),
            confidenceLevel: 'low',
            requiresManualReview: false,
            summary: "Defaulted to buyer country: {$country->code}",
        );
    }

    public function meetsEuEvidenceThreshold(): bool
    {
        return $this->evidenceUsed->matchingCountryCount() >= 2
            && count($this->evidenceUsed->countrySignals()) === 1;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'resolvedCountry' => $this->resolvedCountry->code,
            'evidenceUsed' => $this->evidenceUsed->toArray(),
            'evidenceIgnored' => $this->evidenceIgnored->toArray(),
            'confidenceLevel' => $this->confidenceLevel,
            'requiresManualReview' => $this->requiresManualReview,
            'summary' => $this->summary,
        ];
    }
}
