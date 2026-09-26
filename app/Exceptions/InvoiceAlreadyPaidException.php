<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

/** Thrown when a payment attempt is made against an invoice that is already paid. */
class InvoiceAlreadyPaidException extends Exception
{
    public function __construct()
    {
        parent::__construct('This invoice has already been paid.');
    }
}
