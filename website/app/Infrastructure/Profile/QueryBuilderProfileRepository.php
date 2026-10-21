<?php

declare(strict_types=1);

namespace App\Infrastructure\Profile;

use App\Application\Profile\Contracts\ProfileRepository;
use App\Application\Profile\DTOs\ProfileRecord;
use App\Application\Profile\DTOs\PublicProfileRecord;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class QueryBuilderProfileRepository implements ProfileRepository
{
    public function findPublic(int $userId): ?PublicProfileRecord
    {
        $row = DB::table('users as u')
            ->leftJoin('media_assets as m', function ($join): void {
                $join->on('m.id', '=', 'u.avatar_media_id')
                    ->where('m.scope', 'USER')
                    ->where('m.status', 'READY')
                    ->whereNull('m.deleted_at');
            })
            ->where('u.id', $userId)
            ->where('u.status', 'ACTIVE')
            ->whereNull('u.deleted_at')
            ->first(['u.id', 'u.name', 'm.secure_url as avatar_url']);

        return $row === null ? null : new PublicProfileRecord(
            (int) $row->id,
            (string) $row->name,
            $row->avatar_url !== null ? (string) $row->avatar_url : null,
        );
    }

    public function find(int $userId): ?ProfileRecord
    {
        $row = DB::table('users as u')
            ->leftJoin('media_assets as m', function ($join): void {
                $join
                    ->on('m.id', '=', 'u.avatar_media_id')
                    ->where('m.scope', 'USER')
                    ->where('m.status', 'READY')
                    ->whereNull('m.deleted_at');
            })
            ->where('u.id', $userId)
            ->whereNull('u.deleted_at')
            ->first([
                'u.id',
                'u.name',
                'u.email',
                'u.avatar_media_id',
                'm.secure_url as avatar_url',
            ]);

        return $row === null
            ? null
            : new ProfileRecord(
                id: (int) $row->id,
                name: (string) $row->name,
                email: (string) $row->email,
                avatarMediaId: $row->avatar_media_id !== null ? (int) $row->avatar_media_id : null,
                avatarUrl: $row->avatar_url !== null ? (string) $row->avatar_url : null,
            );
    }

    public function passwordHash(int $userId): ?string
    {
        $hash = DB::table('users')
            ->where('id', $userId)
            ->whereNull('deleted_at')
            ->value('password');

        return $hash !== null ? (string) $hash : null;
    }

    public function updateName(int $userId, string $name): void
    {
        $this->updateUser($userId, ['name' => $name, 'updated_at' => now()]);
    }

    public function updatePassword(int $userId, string $passwordHash): void
    {
        DB::transaction(function () use ($userId, $passwordHash): void {
            $this->updateUser($userId, [
                'password' => $passwordHash,
                'remember_token' => null,
                'updated_at' => now(),
            ]);

            DB::table('sessions')->where('user_id', $userId)->delete();
        });
    }

    public function updateAvatar(int $userId, ?int $mediaId): void
    {
        $this->updateUser($userId, ['avatar_media_id' => $mediaId, 'updated_at' => now()]);
    }

    /** @param array<string, mixed> $values */
    private function updateUser(int $userId, array $values): void
    {
        $updated = DB::table('users')
            ->where('id', $userId)
            ->whereNull('deleted_at')
            ->update($values);

        if (
            $updated === 0 &&
            ! DB::table('users')->where('id', $userId)->whereNull('deleted_at')->exists()
        ) {
            throw new RuntimeException('Profile not found.');
        }
    }
}
