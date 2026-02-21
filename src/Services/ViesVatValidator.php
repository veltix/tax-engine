<?php

declare(strict_types=1);

namespace Veltix\TaxEngine\Services;

use Illuminate\Support\Facades\Http;
use Veltix\TaxEngine\Contracts\VatValidatorContract;
use Veltix\TaxEngine\Data\VatValidationResultData;
use Veltix\TaxEngine\Exceptions\VatValidationException;
use Veltix\TaxEngine\Support\VatFormatPatterns;

final class ViesVatValidator implements VatValidatorContract
{
    private const string VIES_URL = 'https://ec.europa.eu/taxation_customs/vies/rest-api/check-vat-number';

    public function __construct(
        private readonly int $timeout = 10,
    ) {}

    public function validate(string $countryCode, string $vatNumber): VatValidationResultData
    {
        $viesPrefix = VatFormatPatterns::viesPrefix($countryCode);

        try {
            $response = Http::timeout($this->timeout)->post(self::VIES_URL, [
                'countryCode' => $viesPrefix,
                'vatNumber' => $vatNumber,
            ]);

            if ($response->failed()) {
                throw VatValidationException::serviceError(
                    "VIES returned HTTP {$response->status()}"
                );
            }

            $data = $response->json();

            if (! isset($data['valid'])) {
                throw VatValidationException::serviceError('VIES response missing valid field');
            }

            $name = $this->filterPlaceholder($data['name'] ?? null);
            $address = $this->filterPlaceholder($data['address'] ?? null);

            if ($data['valid']) {
                return VatValidationResultData::validResult(
                    $countryCode,
                    $vatNumber,
                    $name,
                    $address,
                );
            }

            return VatValidationResultData::invalid(
                $countryCode,
                $vatNumber,
                'VAT number is not valid according to VIES',
            );
        } catch (VatValidationException $e) {
            throw $e;
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            throw VatValidationException::serviceUnavailable(
                'Could not connect to VIES service: ' . $e->getMessage()
            );
        } catch (\Throwable $e) {
            throw VatValidationException::serviceError(
                'VIES validation failed: ' . $e->getMessage()
            );
        }
    }

    private function filterPlaceholder(?string $value): ?string
    {
        if ($value === null || trim($value) === '---') {
            return null;
        }

        return $value;
    }
}
