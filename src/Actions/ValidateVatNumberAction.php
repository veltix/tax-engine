<?php

declare(strict_types=1);

namespace Veltix\TaxEngine\Actions;

use Veltix\TaxEngine\Data\VatValidationResultData;
use Veltix\TaxEngine\Services\VatValidatorService;

final class ValidateVatNumberAction
{
    public function __construct(
        private readonly VatValidatorService $service,
    ) {}

    public function execute(string $vatNumber, ?string $countryCode = null): VatValidationResultData
    {
        return $this->service->validate($vatNumber, $countryCode);
    }
}
