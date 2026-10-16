<?php

declare(strict_types=1);

namespace App\Application\Identity\Contracts;

use App\Application\Identity\DTOs\IssuedAccessToken;
use App\Application\Identity\Exceptions\InvalidAccessToken;

interface AccessTokenIssuer
{
    public function issue(int $userId): IssuedAccessToken;

    /**
     * Returns the user ID the token was issued for.
     *
     * @throws InvalidAccessToken
     */
    public function verify(string $token): int;
}
