<?php

declare(strict_types=1);

namespace App\Application\Identity\DTOs;

use DateTimeImmutable;

final readonly class AuthTokens
{
    public function __construct(
        public string $accessToken,
        public DateTimeImmutable $accessTokenExpiresAt,
        public string $refreshToken,
        public DateTimeImmutable $refreshTokenExpiresAt,
    ) {}
}
