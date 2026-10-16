<?php

declare(strict_types=1);

namespace App\Application\Identity\UseCases\RevokeRefreshToken;

use App\Application\Identity\Contracts\RefreshTokenRepository;
use App\Application\Identity\Services\TokenPairFactory;
use DateTimeImmutable;

final readonly class RevokeRefreshTokenService implements RevokeRefreshTokenUseCase
{
    public function __construct(private RefreshTokenRepository $refreshTokens) {}

    public function execute(int $userId, string $refreshToken): void
    {
        $record = $this->refreshTokens->findByHash(TokenPairFactory::hash($refreshToken));

        if ($record === null || $record->userId !== $userId) {
            return;
        }

        $this->refreshTokens->claim($record->id, new DateTimeImmutable);
    }
}
