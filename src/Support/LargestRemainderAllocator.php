<?php

declare(strict_types=1);

namespace Veltix\TaxEngine\Support;

final class LargestRemainderAllocator
{
    /**
     * Distribute an integer total across items using the largest remainder method.
     *
     * @param  int      $total             The total to distribute (in minor units)
     * @param  string[] $exactFractional   Exact unrounded amounts as bcmath strings
     * @return int[]                       Allocated amounts in original input order
     */
    public static function allocate(int $total, array $exactFractional): array
    {
        if ($exactFractional === []) {
            return [];
        }

        $floors = [];
        $remainders = [];

        foreach ($exactFractional as $index => $val) {
            $floored = bcmul('1', $val, 0);
            $floors[$index] = (int) $floored;
            $remainder = bcsub($val, $floored, 10);
            $remainders[$index] = $remainder;
        }

        $leftover = $total - array_sum($floors);

        $indices = array_keys($exactFractional);

        usort($indices, function (int $a, int $b) use ($remainders): int {
            $cmp = bccomp($remainders[$b], $remainders[$a], 10);

            return $cmp !== 0 ? $cmp : $a <=> $b;
        });

        for ($i = 0; $i < $leftover; $i++) {
            $floors[$indices[$i]]++;
        }

        return $floors;
    }
}
