<?php

declare(strict_types=1);

use Veltix\TaxEngine\Data\EvidenceData;
use Veltix\TaxEngine\Data\EvidenceItemData;
use Veltix\TaxEngine\Data\ResolvedLocationData;
use Veltix\TaxEngine\Support\Country;

it('creates from buyer country with defaults', function () {
    $country = new Country('DE');
    $location = ResolvedLocationData::fromBuyerCountry($country);

    expect($location->resolvedCountry->code)->toBe('DE')
        ->and($location->confidenceLevel)->toBe('low')
        ->and($location->requiresManualReview)->toBeFalse()
        ->and($location->evidenceUsed->isEmpty())->toBeTrue()
        ->and($location->evidenceIgnored->isEmpty())->toBeTrue()
        ->and($location->summary)->toContain('DE');
});

it('meets EU evidence threshold with 2+ matching non-contradictory signals', function () {
    $evidence = EvidenceData::fromItems(
        EvidenceItemData::billingAddress('DE'),
        EvidenceItemData::ipAddress('DE'),
    );

    $location = new ResolvedLocationData(
        resolvedCountry: new Country('DE'),
        evidenceUsed: $evidence,
        evidenceIgnored: EvidenceData::empty(),
        confidenceLevel: 'medium',
        requiresManualReview: false,
    );

    expect($location->meetsEuEvidenceThreshold())->toBeTrue();
});

it('does not meet EU evidence threshold with fewer than 2 signals', function () {
    $evidence = EvidenceData::fromItems(
        EvidenceItemData::billingAddress('DE'),
    );

    $location = new ResolvedLocationData(
        resolvedCountry: new Country('DE'),
        evidenceUsed: $evidence,
        evidenceIgnored: EvidenceData::empty(),
        confidenceLevel: 'low',
        requiresManualReview: false,
    );

    expect($location->meetsEuEvidenceThreshold())->toBeFalse();
});

it('does not meet EU evidence threshold with conflicting signals', function () {
    $evidence = EvidenceData::fromItems(
        EvidenceItemData::billingAddress('DE'),
        EvidenceItemData::ipAddress('FR'),
    );

    $location = new ResolvedLocationData(
        resolvedCountry: new Country('DE'),
        evidenceUsed: $evidence,
        evidenceIgnored: EvidenceData::empty(),
        confidenceLevel: 'low',
        requiresManualReview: true,
    );

    expect($location->meetsEuEvidenceThreshold())->toBeFalse();
});

it('converts to array', function () {
    $location = ResolvedLocationData::fromBuyerCountry(new Country('NL'));

    $array = $location->toArray();

    expect($array['resolvedCountry'])->toBe('NL')
        ->and($array['confidenceLevel'])->toBe('low')
        ->and($array['requiresManualReview'])->toBeFalse()
        ->and($array)->toHaveKeys(['evidenceUsed', 'evidenceIgnored', 'summary']);
});
