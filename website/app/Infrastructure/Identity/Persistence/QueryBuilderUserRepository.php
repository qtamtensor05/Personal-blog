<?php

declare(strict_types=1);

namespace App\Infrastructure\Identity\Persistence;

use App\Application\Identity\Contracts\UserRepository;
use App\Application\Identity\Exceptions\EmailAlreadyRegistered;
use App\Domain\Identity\Entities\User;
use App\Domain\Identity\Enums\UserStatus;
use App\Domain\Identity\ValueObjects\EmailAddress;
use DateTimeImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class QueryBuilderUserRepository implements UserRepository
{
    private const TABLE = 'users';

    public function findById(int $id): ?User
    {
        $record = DB::table(self::TABLE)->where('id', $id)->whereNull('deleted_at')->first();

        return $record === null ? null : $this->toDomain($record);
    }

    public function findByEmail(EmailAddress $email): ?User
    {
        $record = DB::table(self::TABLE)
            ->where('email', $email->value())
            ->whereNull('deleted_at')
            ->first();

        return $record === null ? null : $this->toDomain($record);
    }

    public function create(string $name, EmailAddress $email, string $passwordHash): User
    {
        $now = now();

        try {
            $id = DB::table(self::TABLE)->insertGetId([
                'name' => $name,
                'email' => $email->value(),
                'password' => $passwordHash,
                'status' => UserStatus::PendingVerification->value,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } catch (QueryException $exception) {
            $errorInfo = $exception->errorInfo ?? [];

            $isDuplicateKey =
                ($errorInfo[0] ?? null) === '23000' && (int) ($errorInfo[1] ?? 0) === 1062;

            if ($isDuplicateKey) {
                throw new EmailAlreadyRegistered;
            }

            throw $exception;
        }

        return new User(
            id: (int) $id,
            name: $name,
            email: $email,
            status: UserStatus::PendingVerification,
        );
    }

    public function update(User $user): void
    {
        $updated = DB::table(self::TABLE)
            ->where('id', $user->id())
            ->whereNull('deleted_at')
            ->update([
                'name' => $user->name(),
                'status' => $user->status()->value,
                'email_verified_at' => $user->emailVerifiedAt()?->format('Y-m-d H:i:s'),
                'updated_at' => now(),
            ]);

        if ($updated === 0) {
            $exists = DB::table(self::TABLE)
                ->where('id', $user->id())
                ->whereNull('deleted_at')
                ->exists();

            if (! $exists) {
                throw new RuntimeException('Cannot update a user that does not exist.');
            }
        }
    }

    public function changePassword(int $userId, string $passwordHash): void
    {
        DB::transaction(function () use ($userId, $passwordHash): void {
            DB::table(self::TABLE)
                ->where('id', $userId)
                ->whereNull('deleted_at')
                ->update([
                    'password' => $passwordHash,
                    'remember_token' => null,
                    'updated_at' => now(),
                ]);

            // Sign the user out everywhere (sessions use the database driver).
            DB::table('sessions')->where('user_id', $userId)->delete();
        });
    }

    private function toDomain(object $record): User
    {
        return new User(
            id: (int) $record->id,
            name: $record->name,
            email: new EmailAddress($record->email),
            status: UserStatus::from($record->status),
            emailVerifiedAt: $record->email_verified_at !== null
                ? new DateTimeImmutable($record->email_verified_at)
                : null,
        );
    }
}
