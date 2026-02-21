<?php

declare(strict_types=1);

use Veltix\TaxEngine\Support\Country;

it('creates with valid code', function () {
    expect((new Country('DE'))->code)->toBe('DE');
});

it('normalizes to uppercase', function () {
    expect((new Country('de'))->code)->toBe('DE');
});

it('trims whitespace', function () {
    expect((new Country(' NL '))->code)->toBe('NL');
});

it('rejects invalid code', function () {
    new Country('INVALID');
})->throws(InvalidArgumentException::class);

it('rejects single character', function () {
    new Country('X');
})->throws(InvalidArgumentException::class);

it('rejects numeric code', function () {
    new Country('12');
})->throws(InvalidArgumentException::class);

it('defines all 27 EU members', function () {
    expect(Country::EU_MEMBERS)->toHaveCount(27);
});

it('returns isEu true for all EU members', function (string $code) {
    expect((new Country($code))->isEu())->toBeTrue();
})->with(Country::EU_MEMBERS);

it('returns isEu false for non-EU countries', function (string $code) {
    expect((new Country($code))->isEu())->toBeFalse();
})->with(['US', 'GB', 'CH', 'NO', 'JP']);

it('returns standard VAT rate for EU countries', function () {
    expect((new Country('DE'))->standardVatRate())->toBe('19.00')
        ->and((new Country('NL'))->standardVatRate())->toBe('21.00')
        ->and((new Country('HU'))->standardVatRate())->toBe('27.00');
});

it('returns null standard VAT rate for non-EU', function () {
    expect((new Country('US'))->standardVatRate())->toBeNull();
});

it('compares isSameAs correctly', function () {
    $a = new Country('DE');
    $b = new Country('DE');
    $c = new Country('FR');

    expect($a->isSameAs($b))->toBeTrue()
        ->and($a->isSameAs($c))->toBeFalse();
});

it('detects isDomesticTo for same country', function () {
    expect((new Country('NL'))->isDomesticTo(new Country('NL')))->toBeTrue();
});

it('detects isCrossBorderEu correctly', function () {
    $de = new Country('DE');
    $fr = new Country('FR');
    $us = new Country('US');

    expect($de->isCrossBorderEu($fr))->toBeTrue()
        ->and($de->isCrossBorderEu($de))->toBeFalse()
        ->and($de->isCrossBorderEu($us))->toBeFalse()
        ->and($us->isCrossBorderEu($de))->toBeFalse();
});

it('detects isOutsideEu correctly', function () {
    expect((new Country('US'))->isOutsideEu())->toBeTrue()
        ->and((new Country('GB'))->isOutsideEu())->toBeTrue()
        ->and((new Country('DE'))->isOutsideEu())->toBeFalse();
});

it('returns 27 Country instances from euMembers', function () {
    $members = Country::euMembers();

    expect($members)->toHaveCount(27)
        ->each->toBeInstanceOf(Country::class);
});

it('has standard rates for all EU members', function (string $code) {
    expect((new Country($code))->standardVatRate())->not->toBeNull();
})->with(Country::EU_MEMBERS);
