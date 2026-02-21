<?php

declare(strict_types=1);

namespace Veltix\TaxEngine\Enums;

enum RoundingStrategy: string
{
    case PerLine = 'per_line';
    case PerInvoice = 'per_invoice';
}
