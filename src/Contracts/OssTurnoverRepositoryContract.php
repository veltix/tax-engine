<?php

declare(strict_types=1);

namespace Veltix\TaxEngine\Contracts;

use DateTimeImmutable;
use Veltix\TaxEngine\Support\Country;

interface OssTurnoverRepositoryContract
{
    public function rollingTwelveMonthTurnoverCents(Country $sellerCountry, DateTimeImmutable $asOf): int;
}
