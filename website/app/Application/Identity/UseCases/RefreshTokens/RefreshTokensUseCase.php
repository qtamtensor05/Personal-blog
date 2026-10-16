<?php

declare(strict_types=1);

namespace App\Application\Identity\UseCases\RefreshTokens;

use App\Application\Identity\DTOs\AuthTokens;

interface RefreshTokensUseCase
{
    public function execute(string $refreshToken, ?string $ipAddress, ?string $userAgent): AuthTokens;
}
