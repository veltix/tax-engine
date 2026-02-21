<?php

declare(strict_types=1);

namespace Veltix\TaxEngine\Contracts;

use Veltix\TaxEngine\Data\VatValidationResultData;

interface VatValidatorContract
{
    public function validate(string $countryCode, string $vatNumber): VatValidationResultData;
}
