<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

/** Thrown when a payment amount is zero, negative, or exceeds the invoice's remaining balance. */
class InvalidPaymentAmountException extends Exception
{
    public function __construct()
    {
        parent::__construct('Payment amount must be greater than zero and cannot exceed the balance due.');
    }
}
