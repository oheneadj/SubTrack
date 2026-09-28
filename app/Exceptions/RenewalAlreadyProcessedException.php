<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

/** Thrown when trying to process a renewal that has already rolled the subscription's expiry. */
class RenewalAlreadyProcessedException extends Exception
{
    public function __construct()
    {
        parent::__construct('This renewal has already been processed.');
    }
}
