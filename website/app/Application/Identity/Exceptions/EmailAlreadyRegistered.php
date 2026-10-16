<?php

declare(strict_types=1);

namespace App\Application\Identity\Exceptions;

use DomainException;

final class EmailAlreadyRegistered extends DomainException
{
    public function __construct()
    {
        parent::__construct('This email address is already registered.');
    }
}
