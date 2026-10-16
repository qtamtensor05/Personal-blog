<?php

declare(strict_types=1);

namespace App\Application\Identity\UseCases\RevokeRefreshToken;

interface RevokeRefreshTokenUseCase
{
    /**
     * Revoke the given refresh token if it belongs to the user. Idempotent.
     */
    public function execute(int $userId, string $refreshToken): void;
}
