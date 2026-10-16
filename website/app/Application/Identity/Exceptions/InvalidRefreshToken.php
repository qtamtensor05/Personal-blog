<?php

declare(strict_types=1);

namespace App\Application\Identity\Exceptions;

use DomainException;

final class InvalidRefreshToken extends DomainException
{
    public function __construct()
    {
        parent::__construct('The refresh token is invalid or expired.');
    }
}
