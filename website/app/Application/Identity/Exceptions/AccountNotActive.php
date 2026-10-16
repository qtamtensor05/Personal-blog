<?php

declare(strict_types=1);

namespace App\Application\Identity\Exceptions;

use DomainException;

final class AccountNotActive extends DomainException
{
    public function __construct()
    {
        parent::__construct('Your account must be active and email verified before signing in.');
    }
}
