<?php

declare(strict_types=1);

namespace Veltix\TaxEngine\Services;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Veltix\TaxEngine\Contracts\VatValidatorContract;
use Veltix\TaxEngine\Data\VatValidationResultData;

final class CachingVatValidator implements VatValidatorContract
{
    public function __construct(
        private readonly VatValidatorContract $inner,
        private readonly CacheRepository $cache,
        private readonly int $ttl = 3600,
    ) {}

    public function validate(string $countryCode, string $vatNumber): VatValidationResultData
    {
        $key = $this->cacheKey($countryCode, $vatNumber);

        $cached = $this->cache->get($key);

        if ($cached instanceof VatValidationResultData) {
            return $cached;
        }

        $result = $this->inner->validate($countryCode, $vatNumber);

        $this->cache->put($key, $result, $this->ttl);

        return $result;
    }

    private function cacheKey(string $countryCode, string $vatNumber): string
    {
        return "tax_engine:vat_validation:{$countryCode}:{$vatNumber}";
    }
}
