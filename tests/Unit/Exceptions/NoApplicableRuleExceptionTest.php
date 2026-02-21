<?php

declare(strict_types=1);

use Veltix\TaxEngine\Exceptions\NoApplicableRuleException;

it('creates exception for transaction', function () {
    $exception = NoApplicableRuleException::forTransaction('txn-123');

    expect($exception)
        ->toBeInstanceOf(NoApplicableRuleException::class)
        ->toBeInstanceOf(RuntimeException::class)
        ->getMessage()->toBe('No applicable tax rule found for transaction: txn-123');
});
