<?php

namespace App\Modules\Identity\Actions;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

final class ResetCurrentPasswordAction
{
    public function execute(array $data): string
    {
        // Resolve before the transaction; lock by primary key, not the changing email index.
        $id = User::where('email', $data['email'])->whereNull('deleted_at')->value('id');

        return DB::transaction(function () use ($data, $id): string {
            $user = $id ? User::whereKey($id)->lockForUpdate()->first() : null;
            if (! $user || $user->deleted_at !== null || $user->email !== $data['email']) {
                return Password::InvalidUser;
            }

            // Lock the identity before broker validation AND token consumption.
            return Password::reset($data, function ($user, $password): void {
                $user->forceFill(['password' => Hash::make($password), 'remember_token' => Str::random(60)])->save();
                DB::table('sessions')->where('user_id', $user->id)->delete();
                DB::afterCommit(fn () => event(new PasswordReset($user)));
            });
        });
    }
}
