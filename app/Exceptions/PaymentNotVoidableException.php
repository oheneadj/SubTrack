<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

/** Thrown when trying to void a payment that isn't manual, or isn't currently Succeeded. */
class PaymentNotVoidableException extends Exception
{
    public function __construct()
    {
        parent::__construct('Only a successful, manually-recorded payment can be voided.');
    }
}
