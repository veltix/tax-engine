<?php

declare(strict_types=1);

use Veltix\TaxEngine\Actions\ValidateVatNumberAction;
use Veltix\TaxEngine\Services\VatValidatorService;

test('can be resolved from container', function () {
    $this->app['config']->set('tax.vat_validation.driver', 'null');

    $action = $this->app->make(ValidateVatNumberAction::class);

    expect($action)->toBeInstanceOf(ValidateVatNumberAction::class);
});

test('validates with null driver', function () {
    $this->app['config']->set('tax.vat_validation.driver', 'null');

    $action = $this->app->make(ValidateVatNumberAction::class);
    $result = $action->execute('DE123456789');

    expect($result->valid)->toBeTrue()
        ->and($result->countryCode)->toBe('DE');
});

test('rejects bad format', function () {
    $this->app['config']->set('tax.vat_validation.driver', 'null');

    $action = $this->app->make(ValidateVatNumberAction::class);
    $result = $action->execute('DE12345');

    expect($result->valid)->toBeFalse()
        ->and($result->formatValid)->toBeFalse();
});
