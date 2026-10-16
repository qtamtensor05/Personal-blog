<?php

declare(strict_types=1);

namespace App\Application\Identity\Contracts;

use App\Application\Identity\DTOs\RefreshTokenRecord;
use DateTimeImmutable;

interface RefreshTokenRepository
{
    public function create(
        int $userId,
        string $tokenHash,
        DateTimeImmutable $expiresAt,
        ?string $ipAddress,
        ?string $userAgent,
    ): int;

    public function findByHash(string $tokenHash): ?RefreshTokenRecord;

    /**
     * Revoke the token only if it is still active.
     * Returns false when another request already revoked it.
     */
    public function claim(int $id, DateTimeImmutable $revokedAt): bool;

    public function linkReplacement(int $id, int $replacedById): void;

    public function revokeAllForUser(int $userId, DateTimeImmutable $revokedAt): void;
}
