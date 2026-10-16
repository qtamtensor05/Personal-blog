<?php

declare(strict_types=1);

namespace App\Infrastructure\Identity\Persistence;

use App\Application\Identity\Contracts\PasswordResetTokenRepository;
use App\Domain\Identity\ValueObjects\EmailAddress;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;

final class QueryBuilderPasswordResetTokenRepository implements PasswordResetTokenRepository
{
    private const TABLE = 'password_reset_tokens';

    public function save(EmailAddress $email, string $tokenHash, DateTimeImmutable $createdAt): void
    {
        DB::table(self::TABLE)->upsert(
            [
                'email' => $email->value(),
                'token' => $tokenHash,
                'created_at' => $createdAt->format('Y-m-d H:i:s'),
            ],
            ['email'],
            ['token', 'created_at'],
        );
    }

    public function find(EmailAddress $email): ?array
    {
        $record = DB::table(self::TABLE)->where('email', $email->value())->first();

        if ($record === null || $record->created_at === null) {
            return null;
        }

        return [
            'token_hash' => (string) $record->token,
            'created_at' => new DateTimeImmutable($record->created_at),
        ];
    }

    public function delete(EmailAddress $email): void
    {
        DB::table(self::TABLE)->where('email', $email->value())->delete();
    }
}
