<?php

namespace App\Modules\Identity\Actions;

use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

final class ResendEmailVerificationAction
{
    public function execute(User $user): void
    {
        if ($user->hasVerifiedEmail()) {
            throw new AuthorizationException(
                'Email này đã được xác minh.',
            );
        }

        $user->sendEmailVerificationNotification();
    }
}
