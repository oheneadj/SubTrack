<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

/** Thrown when trying to process a renewal (roll the subscription's expiry) before it's been paid. */
class RenewalNotPaidException extends Exception
{
    public function __construct()
    {
        parent::__construct('This renewal must be paid before it can be processed.');
    }
}
