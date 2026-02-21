<?php

declare(strict_types=1);

namespace Veltix\TaxEngine\Services;

use Veltix\TaxEngine\Contracts\VatValidatorContract;
use Veltix\TaxEngine\Data\VatValidationResultData;

final class NullVatValidator implements VatValidatorContract
{
    public function validate(string $countryCode, string $vatNumber): VatValidationResultData
    {
        return VatValidationResultData::validResult($countryCode, $vatNumber);
    }
}
