<?php

declare(strict_types=1);

namespace Veltix\TaxEngine\Facades;

use Illuminate\Support\Facades\Facade;
use Veltix\TaxEngine\Actions\CalculateTaxAction;
use Veltix\TaxEngine\Data\TaxResultData;
use Veltix\TaxEngine\Data\TransactionData;
use Veltix\TaxEngine\Data\VatValidationResultData;

/**
 * @method static TaxResultData calculate(TransactionData $transaction, ?VatValidationResultData $vatResult = null, array<string, mixed> $metadata = [])
 *
 * @see \Veltix\TaxEngine\Actions\CalculateTaxAction
 */
final class TaxEngine extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return CalculateTaxAction::class;
    }
}
