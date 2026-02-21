<?php

declare(strict_types=1);

namespace Veltix\TaxEngine\Enums;

enum VatOutagePolicy: string
{
    case Allow = 'allow';
    case Deny = 'deny';
    case Queue = 'queue';
}
