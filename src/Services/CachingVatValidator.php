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
        $cached = $this->cache->get($this->cacheKey($countryCode, $vatNumber));

        if ($cached instanceof VatValidationResultData) {
            return $cached;
        }

        return $this->refresh($countryCode, $vatNumber);
    }

    /**
     * Asks the inner validator even while an answer is cached, and caches the new one.
     * Use it where a stale answer costs money, such as when an order is placed.
     *
     * Only answers are cached: a validator that could not check the number throws
     * (VIES "member state unavailable" included), and nothing is stored.
     */
    public function refresh(string $countryCode, string $vatNumber): VatValidationResultData
    {
        $result = $this->inner->validate($countryCode, $vatNumber);

        $this->cache->put($this->cacheKey($countryCode, $vatNumber), $result, $this->ttl);

        return $result;
    }

    private function cacheKey(string $countryCode, string $vatNumber): string
    {
        return "tax_engine:vat_validation:{$countryCode}:{$vatNumber}";
    }
}
