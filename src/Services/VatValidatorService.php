<?php

declare(strict_types=1);

namespace Veltix\TaxEngine\Services;

use Veltix\TaxEngine\Contracts\VatValidatorContract;
use Veltix\TaxEngine\Data\VatValidationResultData;
use Veltix\TaxEngine\Support\Country;

final class VatValidatorService
{
    public function __construct(
        private readonly VatValidatorContract $validator,
    ) {}

    public function validate(string $vatNumber, ?string $countryCode = null): VatValidationResultData
    {
        [$parsedCountry, $parsedNumber] = VatFormatValidator::parse($vatNumber, $countryCode);

        if ($parsedCountry === '' || $parsedNumber === '') {
            return VatValidationResultData::invalid(
                $parsedCountry,
                $parsedNumber,
                'Could not determine country code or VAT number',
                formatValid: false,
            );
        }

        if (! VatFormatValidator::isValidFormat($parsedCountry, $parsedNumber)) {
            return VatValidationResultData::invalid(
                $parsedCountry,
                $parsedNumber,
                "VAT number format is invalid for country {$parsedCountry}",
                formatValid: false,
            );
        }

        if (! $this->isEuCountry($parsedCountry)) {
            return VatValidationResultData::invalid(
                $parsedCountry,
                $parsedNumber,
                "Country {$parsedCountry} is not an EU member state; VIES validation is not available",
            );
        }

        return $this->validator->validate($parsedCountry, $parsedNumber);
    }

    private function isEuCountry(string $countryCode): bool
    {
        return in_array(strtoupper($countryCode), Country::EU_MEMBERS, true);
    }
}
