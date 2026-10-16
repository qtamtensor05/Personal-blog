<?php

declare(strict_types=1);

namespace App\Application\Identity\Exceptions;

use DomainException;

final class InvalidCredentials extends DomainException
{
    public function __construct()
    {
        parent::__construct('These credentials do not match our records.');
    }
}
