<?php

declare(strict_types=1);

namespace Veltix\TaxEngine\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Veltix\TaxEngine\Exceptions\VatValidationException;
use Veltix\TaxEngine\Services\VatValidatorService;

final class ValidVatNumber implements ValidationRule
{
    public function __construct(
        private readonly ?string $countryCode = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || trim($value) === '') {
            $fail('The :attribute must be a valid VAT number.');

            return;
        }

        $service = app(VatValidatorService::class);

        try {
            $result = $service->validate($value, $this->countryCode);

            if (! $result->valid) {
                $fail('The :attribute is not a valid VAT number.');
            }
        } catch (VatValidationException) {
            // Fail-open: external service outage should not block forms
            report(VatValidationException::serviceUnavailable('VIES validation skipped due to service error'));
        }
    }
}
