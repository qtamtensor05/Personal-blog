<?php

declare(strict_types=1);

namespace App\Application\Identity\Exceptions;

use DomainException;

final class InvalidPasswordResetToken extends DomainException
{
    public function __construct()
    {
        parent::__construct('This password reset link is invalid or has expired.');
    }
}
