<?php

namespace App\Domain\Dns\Exceptions;

class MaxRenewalFailuresException extends \RuntimeException
{
    public function __construct(public $cert)
    {
        parent::__construct("Max renewal failures reached for certificate {$cert->id}");
    }
}
