<?php

declare(strict_types=1);

use Veltix\TaxEngine\Support\LargestRemainderAllocator;

it('distributes 10 equal items at 21% correctly', function () {
    // 10 items at 145 cents each, 21% tax
    // Exact tax per item: 145 * 0.21 = 30.45
    // Group total: 1450 * 0.21 = 304.5 -> rounds to 305
    // Floors: 10 x 30 = 300, leftover = 5
    // All remainders equal (0.45), so first 5 indices get +1
    $rateFactor = bcdiv('21.00', '100', 10);
    $exactFractionals = [];
    for ($i = 0; $i < 10; $i++) {
        $exactFractionals[$i] = bcmul('145', $rateFactor, 10);
    }

    $result = LargestRemainderAllocator::allocate(305, $exactFractionals);

    expect($result)->toHaveCount(10)
        ->and(array_sum($result))->toBe(305);

    // First 5 items get 31, last 5 get 30 (tiebreaker by index)
    for ($i = 0; $i < 5; $i++) {
        expect($result[$i])->toBe(31);
    }
    for ($i = 5; $i < 10; $i++) {
        expect($result[$i])->toBe(30);
    }
});

it('handles unequal items with exact division', function () {
    // Two items: exact tax 20.0000 and 30.0000 -> total 50
    // No leftover, floors match exactly
    $result = LargestRemainderAllocator::allocate(50, [
        0 => '20.0000000000',
        1 => '30.0000000000',
    ]);

    expect($result[0])->toBe(20)
        ->and($result[1])->toBe(30)
        ->and(array_sum($result))->toBe(50);
});

it('handles zero total', function () {
    $result = LargestRemainderAllocator::allocate(0, [
        0 => '0.0000000000',
        1 => '0.0000000000',
    ]);

    expect($result[0])->toBe(0)
        ->and($result[1])->toBe(0);
});

it('handles single item', function () {
    $result = LargestRemainderAllocator::allocate(305, [
        0 => '304.5000000000',
    ]);

    expect($result[0])->toBe(305);
});

it('returns empty array for empty input', function () {
    $result = LargestRemainderAllocator::allocate(0, []);

    expect($result)->toBe([]);
});

it('allocates leftover to items with largest remainders first', function () {
    // Three items with different fractional parts
    // Item 0: 10.2 -> floor 10, remainder 0.2
    // Item 1: 10.8 -> floor 10, remainder 0.8
    // Item 2: 10.5 -> floor 10, remainder 0.5
    // Total = 32, sum of floors = 30, leftover = 2
    // Sort by remainder desc: item1 (0.8), item2 (0.5), item0 (0.2)
    // item1 gets +1 = 11, item2 gets +1 = 11, item0 stays 10
    $result = LargestRemainderAllocator::allocate(32, [
        0 => '10.2000000000',
        1 => '10.8000000000',
        2 => '10.5000000000',
    ]);

    expect($result[0])->toBe(10)
        ->and($result[1])->toBe(11)
        ->and($result[2])->toBe(11)
        ->and(array_sum($result))->toBe(32);
});
