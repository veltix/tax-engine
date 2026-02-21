<?php

declare(strict_types=1);

namespace Veltix\TaxEngine\Enums;

enum CustomerType: string
{
    case B2B = 'b2b';
    case B2C = 'b2c';
    case Government = 'gov';
}
