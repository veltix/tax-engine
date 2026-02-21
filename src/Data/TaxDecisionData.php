<?php

declare(strict_types=1);

namespace Veltix\TaxEngine\Data;

use DateTimeImmutable;
use Veltix\TaxEngine\Enums\TaxScheme;
use Veltix\TaxEngine\Support\Country;

final readonly class TaxDecisionData
{
    public function __construct(
        public TaxScheme $scheme,
        public string $rate,
        public Country $taxCountry,
        public string $ruleApplied,
        public string $reasoning,
        public bool $vatNumberValidated = false,
        public bool $reverseCharged = false,
        public ?DateTimeImmutable $decidedAt = null,
        /** @var array<string, mixed> */
        public array $evidence = [],
    ) {}

    public function isZeroRated(): bool
    {
        /** @var numeric-string $rate */
        $rate = $this->rate;

        return bccomp($rate, '0.00', 2) === 0;
    }

    public function isExempt(): bool
    {
        return $this->scheme === TaxScheme::Exempt;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'scheme' => $this->scheme->value,
            'rate' => $this->rate,
            'taxCountry' => $this->taxCountry->code,
            'ruleApplied' => $this->ruleApplied,
            'reasoning' => $this->reasoning,
            'vatNumberValidated' => $this->vatNumberValidated,
            'reverseCharged' => $this->reverseCharged,
            'decidedAt' => $this->decidedAt?->format('c'),
            'evidence' => $this->evidence,
        ];
    }
}
