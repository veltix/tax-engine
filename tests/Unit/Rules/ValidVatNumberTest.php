<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Validator;
use Veltix\TaxEngine\Contracts\VatValidatorContract;
use Veltix\TaxEngine\Exceptions\VatValidationException;
use Veltix\TaxEngine\Rules\ValidVatNumber;

beforeEach(function () {
    $this->app['config']->set('tax.vat_validation.driver', 'null');
});

test('passes for valid VAT number', function () {
    $validator = Validator::make(
        ['vat' => 'NL123456789B01'],
        ['vat' => new ValidVatNumber()]
    );

    expect($validator->passes())->toBeTrue();
});

test('fails for invalid format', function () {
    $validator = Validator::make(
        ['vat' => 'DE12345'],
        ['vat' => new ValidVatNumber()]
    );

    expect($validator->fails())->toBeTrue();
});

test('fails for empty value', function () {
    $validator = Validator::make(
        ['vat' => ''],
        ['vat' => ['required', new ValidVatNumber()]]
    );

    expect($validator->fails())->toBeTrue();
});

test('fail-open on VIES error', function () {
    // Bind a mock validator that throws VatValidationException
    $mock = Mockery::mock(VatValidatorContract::class);
    $mock->shouldReceive('validate')
        ->andThrow(VatValidationException::serviceUnavailable());

    $this->app->instance(VatValidatorContract::class, $mock);

    // Rebind the service so it picks up the mock
    $this->app->forgetInstance(\Veltix\TaxEngine\Services\VatValidatorService::class);
    $this->app->singleton(\Veltix\TaxEngine\Services\VatValidatorService::class, function ($app) {
        return new \Veltix\TaxEngine\Services\VatValidatorService(
            $app->make(VatValidatorContract::class),
        );
    });

    $validator = Validator::make(
        ['vat' => 'DE123456789'],
        ['vat' => new ValidVatNumber()]
    );

    // Should pass because fail-open catches VatValidationException
    expect($validator->passes())->toBeTrue();
});

test('accepts country code parameter', function () {
    $validator = Validator::make(
        ['vat' => '123456789B01'],
        ['vat' => new ValidVatNumber('NL')]
    );

    expect($validator->passes())->toBeTrue();
});
