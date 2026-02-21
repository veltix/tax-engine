<?php

declare(strict_types=1);

use Veltix\TaxEngine\Contracts\RateRepositoryContract;
use Veltix\TaxEngine\Enums\SupplyType;
use Veltix\TaxEngine\Exceptions\RateNotFoundException;
use Veltix\TaxEngine\Repositories\StaticRateRepository;
use Veltix\TaxEngine\Support\Country;

it('implements RateRepositoryContract', function () {
    expect(new StaticRateRepository())->toBeInstanceOf(RateRepositoryContract::class);
});

it('returns standard rate for EU countries', function (string $code, string $expected) {
    $repo = new StaticRateRepository();

    expect($repo->standardRate(new Country($code)))->toBe($expected);
})->with([
    ['DE', '19.00'],
    ['FR', '20.00'],
    ['NL', '21.00'],
    ['HU', '27.00'],
    ['LU', '17.00'],
]);

it('throws RateNotFoundException for non-EU country standard rate', function () {
    $repo = new StaticRateRepository();

    $repo->standardRate(new Country('US'));
})->throws(RateNotFoundException::class, 'No VAT rate found for country: US');

it('returns reduced rates for EU countries', function () {
    $repo = new StaticRateRepository();

    $rates = $repo->reducedRates(new Country('FR'));

    expect($rates)
        ->toHaveKeys(['reduced', 'super_reduced', 'reduced_second'])
        ->and($rates['reduced'])->toBe('5.50')
        ->and($rates['super_reduced'])->toBe('2.10');
});

it('returns empty array for countries without reduced rates', function () {
    $repo = new StaticRateRepository();

    expect($repo->reducedRates(new Country('DK')))->toBe([]);
});

it('returns empty array for non-EU country reduced rates', function () {
    $repo = new StaticRateRepository();

    expect($repo->reducedRates(new Country('US')))->toBe([]);
});

it('resolves rate for supply type using standard rate', function () {
    $repo = new StaticRateRepository();

    expect($repo->rateForSupplyType(new Country('DE'), SupplyType::DigitalServices))->toBe('19.00')
        ->and($repo->rateForSupplyType(new Country('DE'), SupplyType::Goods))->toBe('19.00')
        ->and($repo->rateForSupplyType(new Country('DE'), SupplyType::Telecommunications))->toBe('19.00')
        ->and($repo->rateForSupplyType(new Country('DE'), SupplyType::Broadcasting))->toBe('19.00')
        ->and($repo->rateForSupplyType(new Country('DE'), SupplyType::Services))->toBe('19.00');
});

it('resolves rate for supply type with category override', function () {
    $repo = new StaticRateRepository();

    expect($repo->rateForSupplyType(new Country('DE'), SupplyType::Goods, 'reduced'))->toBe('7.00')
        ->and($repo->rateForSupplyType(new Country('FR'), SupplyType::Goods, 'super_reduced'))->toBe('2.10')
        ->and($repo->rateForSupplyType(new Country('IE'), SupplyType::Services, 'parking'))->toBe('13.50');
});

it('falls back to standard rate when category not found', function () {
    $repo = new StaticRateRepository();

    expect($repo->rateForSupplyType(new Country('DE'), SupplyType::Goods, 'super_reduced'))->toBe('19.00');
});

it('throws RateNotFoundException for non-EU country supply type', function () {
    $repo = new StaticRateRepository();

    $repo->rateForSupplyType(new Country('US'), SupplyType::Goods);
})->throws(RateNotFoundException::class);
