<?php

declare(strict_types=1);

namespace App\Application\Identity\Services;

use App\Application\Identity\Contracts\AccessTokenIssuer;
use App\Application\Identity\Contracts\RefreshTokenRepository;
use App\Application\Identity\DTOs\AuthTokens;
use DateTimeImmutable;

/**
 * Issues an access token plus a new opaque refresh token.
 * Only the SHA-256 hash of the refresh token is persisted.
 */
final readonly class TokenPairFactory
{
    public function __construct(
        private AccessTokenIssuer $accessTokens,
        private RefreshTokenRepository $refreshTokens,
        private int $refreshTtlDays,
    ) {}

    public function create(
        int $userId,
        ?string $ipAddress,
        ?string $userAgent,
        ?int $replacesTokenId = null,
    ): AuthTokens {
        $access = $this->accessTokens->issue($userId);

        $refreshToken = rtrim(strtr(base64_encode(random_bytes(48)), '+/', '-_'), '=');
        $refreshExpiresAt = (new DateTimeImmutable)->modify("+{$this->refreshTtlDays} days");

        $refreshId = $this->refreshTokens->create(
            $userId,
            self::hash($refreshToken),
            $refreshExpiresAt,
            $ipAddress,
            $userAgent,
        );

        if ($replacesTokenId !== null) {
            $this->refreshTokens->linkReplacement($replacesTokenId, $refreshId);
        }

        return new AuthTokens($access->token, $access->expiresAt, $refreshToken, $refreshExpiresAt);
    }

    public static function hash(string $refreshToken): string
    {
        return hash('sha256', $refreshToken);
    }
}
