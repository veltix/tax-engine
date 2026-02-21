<?php

declare(strict_types=1);

use Veltix\TaxEngine\Data\TaxDecisionData;
use Veltix\TaxEngine\Enums\TaxScheme;
use Veltix\TaxEngine\Support\Country;

it('constructs with all properties', function () {
    $decision = new TaxDecisionData(
        scheme: TaxScheme::Standard,
        rate: '21.00',
        taxCountry: new Country('NL'),
        ruleApplied: 'DomesticStandardRule',
        reasoning: 'Domestic B2C sale in NL',
        vatNumberValidated: true,
        reverseCharged: false,
        decidedAt: new DateTimeImmutable('2025-01-15'),
        evidence: ['vat_valid' => true],
    );

    expect($decision->scheme)->toBe(TaxScheme::Standard)
        ->and($decision->rate)->toBe('21.00')
        ->and($decision->taxCountry->code)->toBe('NL')
        ->and($decision->ruleApplied)->toBe('DomesticStandardRule')
        ->and($decision->reasoning)->toBe('Domestic B2C sale in NL')
        ->and($decision->vatNumberValidated)->toBeTrue()
        ->and($decision->reverseCharged)->toBeFalse()
        ->and($decision->evidence)->toBe(['vat_valid' => true]);
});

it('is zero rated when rate is zero', function () {
    expect(makeDecision(rate: '0.00')->isZeroRated())->toBeTrue();
});

it('is not zero rated when rate is positive', function () {
    expect(makeDecision(rate: '21.00')->isZeroRated())->toBeFalse();
});

it('is exempt when scheme is Exempt', function () {
    expect(makeDecision(scheme: TaxScheme::Exempt)->isExempt())->toBeTrue();
});

it('is not exempt for other schemes', function () {
    expect(makeDecision(scheme: TaxScheme::Standard)->isExempt())->toBeFalse();
});

it('serializes to array', function () {
    $array = makeDecision()->toArray();

    expect($array['scheme'])->toBe('standard')
        ->and($array['rate'])->toBe('21.00')
        ->and($array['taxCountry'])->toBe('NL')
        ->and($array['ruleApplied'])->toBe('TestRule')
        ->and($array['reasoning'])->toBe('Test reasoning')
        ->and($array['vatNumberValidated'])->toBeFalse()
        ->and($array['reverseCharged'])->toBeFalse()
        ->and($array['decidedAt'])->toBeNull()
        ->and($array['evidence'])->toBe([]);
});

function makeDecision(
    TaxScheme $scheme = TaxScheme::Standard,
    string $rate = '21.00',
): TaxDecisionData {
    return new TaxDecisionData(
        scheme: $scheme,
        rate: $rate,
        taxCountry: new Country('NL'),
        ruleApplied: 'TestRule',
        reasoning: 'Test reasoning',
    );
}
