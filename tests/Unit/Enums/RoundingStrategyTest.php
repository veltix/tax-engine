<?php

declare(strict_types=1);

use Veltix\TaxEngine\Enums\RoundingStrategy;

it('has per line case', function () {
    expect(RoundingStrategy::PerLine->value)->toBe('per_line');
});

it('has per invoice case', function () {
    expect(RoundingStrategy::PerInvoice->value)->toBe('per_invoice');
});

it('can be created from string', function () {
    expect(RoundingStrategy::from('per_line'))->toBe(RoundingStrategy::PerLine)
        ->and(RoundingStrategy::from('per_invoice'))->toBe(RoundingStrategy::PerInvoice);
});
