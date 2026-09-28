<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

/** Thrown when a payment can no longer be edited in place — void and re-record it instead. */
class PaymentNotEditableException extends Exception
{
    public function __construct()
    {
        parent::__construct('This payment can no longer be edited directly — a receipt may already document it. Void it and record a new payment instead.');
    }
}
