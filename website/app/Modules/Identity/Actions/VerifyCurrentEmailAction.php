<?php

namespace App\Modules\Identity\Actions;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\DB;

final class VerifyCurrentEmailAction
{
    public function execute(int $userId, string $hash): void
    {
        DB::transaction(function () use ($userId, $hash): void {
            $user = User::whereKey($userId)->lockForUpdate()->firstOrFail();
            abort_unless($user->deleted_at === null && hash_equals(sha1($user->getEmailForVerification()), $hash), 403);
            if (! $user->hasVerifiedEmail()) {
                $user->markEmailAsVerified();
                DB::afterCommit(fn () => event(new Verified($user)));
            }
            if ($user->status === 'PENDING_VERIFICATION') {
                $user->forceFill(['status' => 'ACTIVE'])->save();
            }
        });
    }
}
