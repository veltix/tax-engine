<?php

declare(strict_types=1);

use Veltix\TaxEngine\Enums\PriceMode;

it('has tax exclusive case', function () {
    expect(PriceMode::TaxExclusive->value)->toBe('tax_exclusive');
});

it('has tax inclusive case', function () {
    expect(PriceMode::TaxInclusive->value)->toBe('tax_inclusive');
});

it('can be created from string', function () {
    expect(PriceMode::from('tax_exclusive'))->toBe(PriceMode::TaxExclusive)
        ->and(PriceMode::from('tax_inclusive'))->toBe(PriceMode::TaxInclusive);
});
