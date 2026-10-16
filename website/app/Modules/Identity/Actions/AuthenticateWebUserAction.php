<?php

namespace App\Modules\Identity\Actions;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

final class AuthenticateWebUserAction
{
    public function execute(
        string $email,
        string $password,
        bool $remember = false,
    ): bool {
        return Auth::guard('web')->attempt(
            [
                'email' => $email,
                'password' => $password,
            ],
            $remember,
        );
    }

    public function logout(Request $request): void
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}
