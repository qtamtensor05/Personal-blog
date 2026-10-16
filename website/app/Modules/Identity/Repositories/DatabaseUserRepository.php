<?php

namespace App\Modules\Identity\Repositories;

use App\Models\User;
use App\Modules\Identity\Contracts\UserRepository;
use Illuminate\Support\Facades\DB;

final class DatabaseUserRepository implements UserRepository
{
    public function findByEmailForAuthentication(string $email): ?User
    {
        $record = DB::table('users')
            ->where('email', $email)
            ->whereNull('deleted_at')
            ->first();

        return $record === null
            ? null
            : (new User)->newFromBuilder((array) $record);
    }

    public function recordSuccessfulLogin(int $userId): void
    {
        DB::table('users')
            ->where('id', $userId)
            ->whereNull('deleted_at')
            ->update([
                'last_login_at' => now(),
                'updated_at' => now(),
            ]);
    }
}
