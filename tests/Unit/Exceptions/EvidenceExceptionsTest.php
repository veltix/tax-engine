<?php

declare(strict_types=1);

use Veltix\TaxEngine\Exceptions\EvidenceConflictException;
use Veltix\TaxEngine\Exceptions\InsufficientEvidenceException;

it('creates conflict detected exception with country list', function () {
    $exception = EvidenceConflictException::conflictDetected(['DE', 'FR', 'NL']);

    expect($exception)->toBeInstanceOf(RuntimeException::class)
        ->and($exception->getMessage())->toContain('DE')
        ->and($exception->getMessage())->toContain('FR')
        ->and($exception->getMessage())->toContain('NL')
        ->and($exception->getMessage())->toContain('conflict');
});

it('creates minimum not met exception with counts', function () {
    $exception = InsufficientEvidenceException::minimumNotMet(2, 1);

    expect($exception)->toBeInstanceOf(RuntimeException::class)
        ->and($exception->getMessage())->toContain('2')
        ->and($exception->getMessage())->toContain('1')
        ->and($exception->getMessage())->toContain('Insufficient');
});
