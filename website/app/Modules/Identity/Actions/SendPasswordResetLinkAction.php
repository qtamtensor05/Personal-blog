<?php

namespace App\Modules\Identity\Actions;

use Illuminate\Support\Facades\Password;

final class SendPasswordResetLinkAction
{
    public function execute(string $email): string
    {
        return Password::sendResetLink([
            'email' => $email,
        ]);
    }
}
