<?php

namespace App\Modules\Identity\Actions;

use App\Models\User;
use App\Modules\Identity\Enums\LoginAccessDecision;

final class DetermineLoginAccessAction
{
    public function execute(User $user): LoginAccessDecision
    {
        $isUnlocked = $user->locked_until === null
            || $user->locked_until->isPast();

        if (
            $user->deleted_at === null
            && $isUnlocked
            && $user->status === 'PENDING_VERIFICATION'
            && ! $user->hasVerifiedEmail()
        ) {
            return LoginAccessDecision::VerificationRequired;
        }

        if (! $user->canAccessApplication()) {
            return LoginAccessDecision::Denied;
        }

        return LoginAccessDecision::Allowed;
    }
}
