<?php

declare(strict_types=1);

namespace Veltix\TaxEngine\Services;

use Veltix\TaxEngine\Data\EvidenceData;
use Veltix\TaxEngine\Data\EvidenceItemData;
use Veltix\TaxEngine\Data\ResolvedCountryDecisionData;
use Veltix\TaxEngine\Data\ResolvedLocationData;
use Veltix\TaxEngine\Exceptions\EvidenceConflictException;
use Veltix\TaxEngine\Exceptions\InsufficientEvidenceException;
use Veltix\TaxEngine\Support\Country;

final class EvidenceValidatorService
{
    public function validate(EvidenceData $evidence, string $mode = 'strict'): ResolvedCountryDecisionData
    {
        $countries = $evidence->countrySignals();
        $majorityCountry = $this->majorityCountry($evidence);

        if ($mode === 'strict') {
            if ($evidence->count() < 2) {
                throw InsufficientEvidenceException::minimumNotMet(2, $evidence->count());
            }

            if (count($countries) > 1) {
                throw EvidenceConflictException::conflictDetected($countries);
            }
        }

        $supporting = new EvidenceData(array_values(array_filter(
            $evidence->items,
            fn (EvidenceItemData $item) => $item->resolvedCountryCode === $majorityCountry,
        )));

        $conflicting = new EvidenceData(array_values(array_filter(
            $evidence->items,
            fn (EvidenceItemData $item) => $item->resolvedCountryCode !== $majorityCountry,
        )));

        $valid = $evidence->count() >= 2 && count($countries) === 1;
        $failureReason = null;

        if (! $valid && $mode === 'tolerant') {
            if ($evidence->count() < 2) {
                $failureReason = 'Insufficient evidence signals';
            } elseif (count($countries) > 1) {
                $failureReason = 'Conflicting evidence signals';
            }
        }

        return new ResolvedCountryDecisionData(
            resolvedCountry: new Country($majorityCountry),
            valid: $valid,
            failureReason: $failureReason,
            supportingEvidence: $supporting,
            conflictingEvidence: $conflicting,
            mode: $mode,
        );
    }

    public function resolveLocation(EvidenceData $evidence, string $mode = 'strict'): ResolvedLocationData
    {
        $decision = $this->validate($evidence, $mode);

        $matchingCount = $evidence->matchingCountryCount();
        $confidenceLevel = match (true) {
            $matchingCount >= 3 => 'high',
            $matchingCount >= 2 => 'medium',
            default => 'low',
        };

        $requiresManualReview = ! $decision->valid && $mode === 'tolerant';

        $summary = $decision->valid
            ? "Resolved to {$decision->resolvedCountry->code} with {$confidenceLevel} confidence"
            : "Resolved to {$decision->resolvedCountry->code} (requires review)";

        return new ResolvedLocationData(
            resolvedCountry: $decision->resolvedCountry,
            evidenceUsed: $decision->supportingEvidence,
            evidenceIgnored: $decision->conflictingEvidence,
            confidenceLevel: $confidenceLevel,
            requiresManualReview: $requiresManualReview,
            summary: $summary,
        );
    }

    private function majorityCountry(EvidenceData $evidence): string
    {
        $counts = array_count_values(
            array_map(fn (EvidenceItemData $item) => $item->resolvedCountryCode, $evidence->items),
        );

        arsort($counts);

        return array_key_first($counts);
    }
}
