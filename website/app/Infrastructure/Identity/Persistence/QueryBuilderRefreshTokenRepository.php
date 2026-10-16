<?php

declare(strict_types=1);

namespace App\Infrastructure\Identity\Persistence;

use App\Application\Identity\Contracts\RefreshTokenRepository;
use App\Application\Identity\DTOs\RefreshTokenRecord;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;

final class QueryBuilderRefreshTokenRepository implements RefreshTokenRepository
{
    private const TABLE = 'refresh_tokens';

    private const FORMAT = 'Y-m-d H:i:s';

    public function create(
        int $userId,
        string $tokenHash,
        DateTimeImmutable $expiresAt,
        ?string $ipAddress,
        ?string $userAgent,
    ): int {
        $now = now();

        return (int) DB::table(self::TABLE)->insertGetId([
            'user_id' => $userId,
            'token_hash' => $tokenHash,
            'expires_at' => $expiresAt->format(self::FORMAT),
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function findByHash(string $tokenHash): ?RefreshTokenRecord
    {
        $record = DB::table(self::TABLE)->where('token_hash', $tokenHash)->first();

        if ($record === null) {
            return null;
        }

        return new RefreshTokenRecord(
            id: (int) $record->id,
            userId: (int) $record->user_id,
            expiresAt: new DateTimeImmutable($record->expires_at),
            revokedAt: $record->revoked_at !== null
                ? new DateTimeImmutable($record->revoked_at)
                : null,
            replacedById: $record->replaced_by_id !== null ? (int) $record->replaced_by_id : null,
        );
    }

    public function claim(int $id, DateTimeImmutable $revokedAt): bool
    {
        return DB::table(self::TABLE)
            ->where('id', $id)
            ->whereNull('revoked_at')
            ->update([
                'revoked_at' => $revokedAt->format(self::FORMAT),
                'updated_at' => now(),
            ]) === 1;
    }

    public function linkReplacement(int $id, int $replacedById): void
    {
        DB::table(self::TABLE)
            ->where('id', $id)
            ->update(['replaced_by_id' => $replacedById, 'updated_at' => now()]);
    }

    public function revokeAllForUser(int $userId, DateTimeImmutable $revokedAt): void
    {
        DB::table(self::TABLE)
            ->where('user_id', $userId)
            ->whereNull('revoked_at')
            ->update([
                'revoked_at' => $revokedAt->format(self::FORMAT),
                'updated_at' => now(),
            ]);
    }
}
