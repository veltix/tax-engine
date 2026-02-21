<?php

declare(strict_types=1);

namespace Veltix\TaxEngine\Services;

use Veltix\TaxEngine\Data\EvidenceData;
use Veltix\TaxEngine\Data\EvidenceItemData;
use Veltix\TaxEngine\Data\TransactionData;
use Veltix\TaxEngine\Data\VatValidationResultData;
use Veltix\TaxEngine\Enums\EvidenceSource;
use Veltix\TaxEngine\Enums\EvidenceType;

final class EvidenceCollectorService
{
    /**
     * @param array<string, string> $additionalSignals
     */
    public function collect(
        TransactionData $transaction,
        ?VatValidationResultData $vatResult = null,
        array $additionalSignals = [],
    ): EvidenceData {
        $evidence = $this->fromTransaction($transaction);

        if ($vatResult !== null) {
            $evidence = $evidence->merge($this->fromVatValidation($vatResult));
        }

        foreach ($this->processAdditionalSignals($additionalSignals) as $item) {
            $evidence = $evidence->add($item);
        }

        return $evidence;
    }

    public function fromTransaction(TransactionData $transaction): EvidenceData
    {
        return EvidenceData::fromItems(
            EvidenceItemData::billingAddress($transaction->buyerCountry->code),
        );
    }

    public function fromVatValidation(VatValidationResultData $vatResult): EvidenceData
    {
        if (! $vatResult->valid) {
            return EvidenceData::empty();
        }

        return EvidenceData::fromItems(
            new EvidenceItemData(
                type: EvidenceType::BillingAddress,
                value: $vatResult->vatNumber,
                resolvedCountryCode: strtoupper($vatResult->countryCode),
                source: EvidenceSource::VatValidation,
                capturedAt: $vatResult->requestDate ?? new \DateTimeImmutable(),
            ),
        );
    }

    /**
     * @param array<string, string> $signals
     * @return EvidenceItemData[]
     */
    private function processAdditionalSignals(array $signals): array
    {
        $map = [
            'ip_country' => fn (string $code) => EvidenceItemData::ipAddress($code),
            'bank_country' => fn (string $code) => EvidenceItemData::bankCountry($code),
            'sim_country' => fn (string $code) => EvidenceItemData::simCountry($code),
            'payment_provider_country' => fn (string $code) => EvidenceItemData::paymentProviderCountry($code),
            'shipping_country' => fn (string $code) => EvidenceItemData::shippingAddress($code),
            'self_declared_country' => fn (string $code) => EvidenceItemData::selfDeclaredCountry($code),
        ];

        $items = [];

        foreach ($signals as $key => $value) {
            if (isset($map[$key]) && $value !== '') {
                $items[] = $map[$key](strtoupper($value));
            }
        }

        return $items;
    }
}
