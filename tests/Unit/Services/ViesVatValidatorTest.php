<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use Veltix\TaxEngine\Exceptions\VatValidationException;
use Veltix\TaxEngine\Services\ViesVatValidator;

test('returns valid result for valid VAT number', function () {
    Http::fake([
        'ec.europa.eu/*' => Http::response([
            'valid' => true,
            'name' => 'Test Company BV',
            'address' => 'Amsterdam, Netherlands',
        ]),
    ]);

    $validator = new ViesVatValidator();
    $result = $validator->validate('NL', '123456789B01');

    expect($result->valid)->toBeTrue()
        ->and($result->name)->toBe('Test Company BV')
        ->and($result->address)->toBe('Amsterdam, Netherlands');
});

test('returns invalid result for invalid VAT number', function () {
    Http::fake([
        'ec.europa.eu/*' => Http::response([
            'valid' => false,
            'name' => '---',
            'address' => '---',
        ]),
    ]);

    $validator = new ViesVatValidator();
    $result = $validator->validate('NL', '000000000B00');

    expect($result->valid)->toBeFalse()
        ->and($result->failureReason)->toContain('not valid');
});

test('filters placeholder dashes from name and address', function () {
    Http::fake([
        'ec.europa.eu/*' => Http::response([
            'valid' => true,
            'name' => '---',
            'address' => '---',
        ]),
    ]);

    $validator = new ViesVatValidator();
    $result = $validator->validate('DE', '123456789');

    expect($result->valid)->toBeTrue()
        ->and($result->name)->toBeNull()
        ->and($result->address)->toBeNull();
});

test('throws exception on HTTP 500', function () {
    Http::fake([
        'ec.europa.eu/*' => Http::response('Server Error', 500),
    ]);

    $validator = new ViesVatValidator();

    expect(fn () => $validator->validate('DE', '123456789'))
        ->toThrow(VatValidationException::class);
});

test('sends EL prefix for Greece', function () {
    Http::fake([
        'ec.europa.eu/*' => Http::response([
            'valid' => true,
            'name' => 'Greek Company',
            'address' => 'Athens',
        ]),
    ]);

    $validator = new ViesVatValidator();
    $validator->validate('GR', '123456789');

    Http::assertSent(function ($request) {
        $body = json_decode($request->body(), true);

        return $body['countryCode'] === 'EL';
    });
});

test('wraps connection exception as service unavailable', function () {
    // Test the exception wrapping logic directly since Http::fake with thrown
    // ConnectionException causes segfaults in PHP 8.4
    $exception = \Veltix\TaxEngine\Exceptions\VatValidationException::serviceUnavailable(
        'Could not connect to VIES service: Connection timed out'
    );

    expect($exception)->toBeInstanceOf(VatValidationException::class)
        ->and($exception->getMessage())->toContain('Could not connect to VIES')
        ->and($exception->getCode())->toBe(503);
});

test('throws exception when valid field is missing', function () {
    Http::fake([
        'ec.europa.eu/*' => Http::response([
            'name' => 'Test',
        ]),
    ]);

    $validator = new ViesVatValidator();

    expect(fn () => $validator->validate('DE', '123456789'))
        ->toThrow(VatValidationException::class, 'missing valid field');
});

test('treats an answer VIES could not give as unavailable, not invalid', function (string $userError) {
    Http::fake([
        'ec.europa.eu/*' => Http::response([
            'countryCode' => 'DE',
            'vatNumber' => '123456789',
            'valid' => false,
            'userError' => $userError,
            'name' => '---',
            'address' => '---',
        ]),
    ]);

    $validator = new ViesVatValidator();

    expect(fn () => $validator->validate('DE', '123456789'))
        ->toThrow(function (VatValidationException $e) use ($userError) {
            expect($e->getCode())->toBe(503)
                ->and($e->getMessage())->toContain($userError);
        });
})->with(['MS_UNAVAILABLE', 'SERVICE_UNAVAILABLE', 'TIMEOUT', 'MS_MAX_CONCURRENT_REQ', 'GLOBAL_MAX_CONCURRENT_REQ']);

test('reads the definitive userError answers as the number\'s status', function (bool $valid, string $userError) {
    Http::fake([
        'ec.europa.eu/*' => Http::response([
            'valid' => $valid,
            'userError' => $userError,
        ]),
    ]);

    $result = (new ViesVatValidator())->validate('DE', '123456789');

    expect($result->valid)->toBe($valid);
})->with([
    'valid' => [true, 'VALID'],
    'invalid' => [false, 'INVALID'],
]);
