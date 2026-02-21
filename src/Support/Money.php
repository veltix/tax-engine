<?php

declare(strict_types=1);

namespace Veltix\TaxEngine\Support;

use InvalidArgumentException;
use Veltix\TaxEngine\Enums\RoundingMode;

/**
 * Minor-unit money representation using bcmath for precision.
 *
 * The $precision parameter defaults to 2 (cents). Currencies with non-standard
 * exponents (e.g. JPY=0, KWD=3) require callers to pass the correct $precision.
 */
final readonly class Money
{
    public function __construct(
        public int $amount,
        public string $currency = 'EUR',
        public int $precision = 2,
    ) {}

    public static function fromCents(int $cents, string $currency = 'EUR', int $precision = 2): self
    {
        return new self($cents, $currency, $precision);
    }

    public static function fromDecimal(
        string $decimal,
        string $currency = 'EUR',
        int $precision = 2,
        RoundingMode $mode = RoundingMode::HalfUp,
    ): self {
        $factor = bcpow('10', (string) $precision);
        /** @var numeric-string $scaled */
        $scaled = bcmul($decimal, $factor, 10);

        $instance = new self(0, $currency, $precision);

        return new self($instance->round($scaled, $mode), $currency, $precision);
    }

    public static function zero(string $currency = 'EUR', int $precision = 2): self
    {
        return new self(0, $currency, $precision);
    }

    public function add(self $other): self
    {
        $this->ensureSameCurrency($other);

        return new self($this->amount + $other->amount, $this->currency, $this->precision);
    }

    public function subtract(self $other): self
    {
        $this->ensureSameCurrency($other);

        return new self($this->amount - $other->amount, $this->currency, $this->precision);
    }

    public function multiply(string $factor, RoundingMode $mode = RoundingMode::HalfUp): self
    {
        /** @var numeric-string $factorNum */
        $factorNum = $factor;
        /** @var numeric-string $result */
        $result = bcmul((string) $this->amount, $factorNum, 10);
        $rounded = $this->round($result, $mode);

        return new self($rounded, $this->currency, $this->precision);
    }

    public function allocateTax(string $ratePercent, RoundingMode $mode = RoundingMode::HalfUp): self
    {
        $factor = bcdiv($ratePercent, '100', 10);
        /** @var numeric-string $result */
        $result = bcmul((string) $this->amount, $factor, 10);
        $rounded = $this->round($result, $mode);

        return new self($rounded, $this->currency, $this->precision);
    }

    public function isZero(): bool
    {
        return $this->amount === 0;
    }

    public function isPositive(): bool
    {
        return $this->amount > 0;
    }

    public function isNegative(): bool
    {
        return $this->amount < 0;
    }

    public function equals(self $other): bool
    {
        return $this->amount === $other->amount
            && $this->currency === $other->currency;
    }

    public function greaterThan(self $other): bool
    {
        $this->ensureSameCurrency($other);

        return $this->amount > $other->amount;
    }

    public function toDecimalString(): string
    {
        $factor = bcpow('10', (string) $this->precision);

        return bcdiv((string) $this->amount, $factor, $this->precision);
    }

    /** @return array{amount: int, currency: string, precision: int, decimal: string} */
    public function toArray(): array
    {
        return [
            'amount' => $this->amount,
            'currency' => $this->currency,
            'precision' => $this->precision,
            'decimal' => $this->toDecimalString(),
        ];
    }

    private function ensureSameCurrency(self $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException(
                "Currency mismatch: cannot operate on {$this->currency} and {$other->currency}"
            );
        }
    }

    /** @param numeric-string $value */
    private function round(string $value, RoundingMode $mode): int
    {
        return match ($mode) {
            RoundingMode::HalfUp => (int) $this->roundHalfUp($value),
            RoundingMode::HalfDown => (int) $this->roundHalfDown($value),
            RoundingMode::HalfEven => (int) $this->roundHalfEven($value),
        };
    }

    /** @param numeric-string $value */
    private function roundHalfUp(string $value): string
    {
        /** @var numeric-string $intPart */
        $intPart = bcmul('1', $value, 0);

        if (bccomp($value, '0', 10) === -1) {
            /** @var numeric-string $fraction */
            $fraction = bcsub($intPart, $value, 10);
            $cmp = bccomp($fraction, '0.5', 10);

            return $cmp >= 0 ? bcsub($intPart, '1', 0) : $intPart;
        }

        /** @var numeric-string $fraction */
        $fraction = bcsub($value, $intPart, 10);
        $cmp = bccomp($fraction, '0.5', 10);

        return $cmp >= 0 ? bcadd($intPart, '1', 0) : $intPart;
    }

    /** @param numeric-string $value */
    private function roundHalfDown(string $value): string
    {
        /** @var numeric-string $intPart */
        $intPart = bcmul('1', $value, 0);

        if (bccomp($value, '0', 10) === -1) {
            /** @var numeric-string $fraction */
            $fraction = bcsub($intPart, $value, 10);
            $cmp = bccomp($fraction, '0.5', 10);

            return $cmp > 0 ? bcsub($intPart, '1', 0) : $intPart;
        }

        /** @var numeric-string $fraction */
        $fraction = bcsub($value, $intPart, 10);
        $cmp = bccomp($fraction, '0.5', 10);

        return $cmp > 0 ? bcadd($intPart, '1', 0) : $intPart;
    }

    /** @param numeric-string $value */
    private function roundHalfEven(string $value): string
    {
        /** @var numeric-string $intPart */
        $intPart = bcmul('1', $value, 0);

        if (bccomp($value, '0', 10) === -1) {
            /** @var numeric-string $fraction */
            $fraction = bcsub($intPart, $value, 10);
        } else {
            /** @var numeric-string $fraction */
            $fraction = bcsub($value, $intPart, 10);
        }

        $cmp = bccomp($fraction, '0.5', 10);

        if ($cmp !== 0) {
            return $this->roundHalfUp($value);
        }

        $intVal = (int) $intPart;
        if ($intVal % 2 === 0) {
            return $intPart;
        }

        return bccomp($value, '0', 10) === -1
            ? bcsub($intPart, '1', 0)
            : bcadd($intPart, '1', 0);
    }
}
