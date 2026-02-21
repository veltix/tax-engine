<?php

declare(strict_types=1);

use Veltix\TaxEngine\Data\EvidenceData;
use Veltix\TaxEngine\Data\EvidenceItemData;
use Veltix\TaxEngine\Data\ResolvedCountryDecisionData;
use Veltix\TaxEngine\Support\Country;

it('can be constructed', function () {
    $decision = new ResolvedCountryDecisionData(
        resolvedCountry: new Country('DE'),
        valid: true,
        failureReason: null,
        supportingEvidence: EvidenceData::fromItems(EvidenceItemData::billingAddress('DE')),
        conflictingEvidence: EvidenceData::empty(),
        mode: 'strict',
    );

    expect($decision->resolvedCountry->code)->toBe('DE')
        ->and($decision->valid)->toBeTrue()
        ->and($decision->failureReason)->toBeNull()
        ->and($decision->mode)->toBe('strict');
});

it('supports tolerant mode with failure reason', function () {
    $decision = new ResolvedCountryDecisionData(
        resolvedCountry: new Country('DE'),
        valid: false,
        failureReason: 'Conflicting evidence signals',
        supportingEvidence: EvidenceData::fromItems(EvidenceItemData::billingAddress('DE')),
        conflictingEvidence: EvidenceData::fromItems(EvidenceItemData::ipAddress('FR')),
        mode: 'tolerant',
    );

    expect($decision->valid)->toBeFalse()
        ->and($decision->failureReason)->toBe('Conflicting evidence signals')
        ->and($decision->mode)->toBe('tolerant')
        ->and($decision->conflictingEvidence->count())->toBe(1);
});

it('converts to array', function () {
    $decision = new ResolvedCountryDecisionData(
        resolvedCountry: new Country('FR'),
        valid: true,
        failureReason: null,
        supportingEvidence: EvidenceData::fromItems(EvidenceItemData::billingAddress('FR')),
        conflictingEvidence: EvidenceData::empty(),
        mode: 'strict',
    );

    $array = $decision->toArray();

    expect($array)->toHaveKeys([
        'resolvedCountry', 'valid', 'failureReason',
        'supportingEvidence', 'conflictingEvidence', 'mode',
    ])
        ->and($array['resolvedCountry'])->toBe('FR')
        ->and($array['valid'])->toBeTrue()
        ->and($array['mode'])->toBe('strict');
});
