<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Application\Identity\Contracts\AccessTokenIssuer;
use App\Application\Identity\DTOs\IssuedAccessToken;
use App\Application\Identity\Exceptions\InvalidAccessToken;
use DateTimeImmutable;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use RuntimeException;
use Throwable;

final readonly class FirebaseJwtAccessTokenIssuer implements AccessTokenIssuer
{
    private const ALGORITHM = 'HS256';

    public function __construct(
        private string $secret,
        private string $issuer,
        private int $ttlMinutes,
    ) {
        if (strlen($this->secret) < 32) {
            throw new RuntimeException('JWT_SECRET must be set and at least 32 bytes long.');
        }
    }

    public function issue(int $userId): IssuedAccessToken
    {
        $issuedAt = new DateTimeImmutable;
        $expiresAt = $issuedAt->modify("+{$this->ttlMinutes} minutes");

        $token = JWT::encode(
            [
                'iss' => $this->issuer,
                'sub' => (string) $userId,
                'iat' => $issuedAt->getTimestamp(),
                'nbf' => $issuedAt->getTimestamp(),
                'exp' => $expiresAt->getTimestamp(),
                'jti' => bin2hex(random_bytes(16)),
                'typ' => 'access',
            ],
            $this->secret,
            self::ALGORITHM,
        );

        return new IssuedAccessToken($token, $expiresAt);
    }

    public function verify(string $token): int
    {
        try {
            $claims = JWT::decode($token, new Key($this->secret, self::ALGORITHM));
        } catch (Throwable) {
            throw new InvalidAccessToken;
        }

        if (
            ($claims->iss ?? null) !== $this->issuer ||
            ($claims->typ ?? null) !== 'access' ||
            ! is_string($claims->sub ?? null) ||
            ! ctype_digit($claims->sub)
        ) {
            throw new InvalidAccessToken;
        }

        return (int) $claims->sub;
    }
}
