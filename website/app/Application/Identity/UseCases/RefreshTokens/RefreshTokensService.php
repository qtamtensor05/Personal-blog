<?php

declare(strict_types=1);

namespace App\Application\Identity\UseCases\RefreshTokens;

use App\Application\Identity\Contracts\RefreshTokenRepository;
use App\Application\Identity\Contracts\UserRepository;
use App\Application\Identity\DTOs\AuthTokens;
use App\Application\Identity\Exceptions\InvalidRefreshToken;
use App\Application\Identity\Services\TokenPairFactory;
use DateTimeImmutable;

final readonly class RefreshTokensService implements RefreshTokensUseCase
{
    public function __construct(
        private RefreshTokenRepository $refreshTokens,
        private UserRepository $users,
        private TokenPairFactory $tokenPairs,
    ) {}

    public function execute(string $refreshToken, ?string $ipAddress, ?string $userAgent): AuthTokens
    {
        $record = $this->refreshTokens->findByHash(TokenPairFactory::hash($refreshToken));
        $now = new DateTimeImmutable;

        if ($record === null) {
            throw new InvalidRefreshToken;
        }

        // A revoked token being presented again means it may have leaked:
        // revoke every session of that user (refresh token reuse detection).
        if ($record->revokedAt !== null || ! $this->refreshTokens->claim($record->id, $now)) {
            $this->refreshTokens->revokeAllForUser($record->userId, $now);

            throw new InvalidRefreshToken;
        }

        if ($record->expiresAt <= $now) {
            throw new InvalidRefreshToken;
        }

        $user = $this->users->findById($record->userId);

        if ($user === null || ! $user->isActive() || $user->emailVerifiedAt() === null) {
            $this->refreshTokens->revokeAllForUser($record->userId, $now);

            throw new InvalidRefreshToken;
        }

        return $this->tokenPairs->create($user->id(), $ipAddress, $userAgent, $record->id);
    }
}
