<?php

declare(strict_types=1);

namespace App\Application\Identity\Exceptions;

use RuntimeException;

final class InvalidAccessToken extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('The access token is invalid or expired.');
    }
}
