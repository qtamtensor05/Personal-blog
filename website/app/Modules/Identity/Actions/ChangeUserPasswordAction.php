<?php

namespace App\Modules\Identity\Actions;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class ChangeUserPasswordAction
{
    public function execute(
        User $user,
        string $currentPassword,
        string $newPassword,
    ): void {
        if (! Hash::check($currentPassword, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => 'Mật khẩu hiện tại không chính xác.',
            ]);
        }

        if ($currentPassword === $newPassword) {
            throw ValidationException::withMessages([
                'password' => 'Mật khẩu mới phải khác mật khẩu hiện tại.',
            ]);
        }

        $user->forceFill([
            'password' => $newPassword,
            'remember_token' => Str::random(60),
        ])->save();
    }
}
