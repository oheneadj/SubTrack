<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

/** Thrown when voiding a payment without a reason while Setting `require_void_reason` is enabled. */
class VoidReasonRequiredException extends Exception
{
    public function __construct()
    {
        parent::__construct('A reason is required to void a payment.');
    }
}
