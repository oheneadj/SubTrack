<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

/** Thrown when a payment's received date is invalid — currently, only that it can't be in the future. */
class InvalidPaymentDateException extends Exception
{
    public function __construct()
    {
        parent::__construct('Payment date cannot be in the future.');
    }
}
