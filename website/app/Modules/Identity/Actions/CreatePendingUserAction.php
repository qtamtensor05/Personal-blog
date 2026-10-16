<?php

namespace App\Modules\Identity\Actions;

use App\Models\User;

final class CreatePendingUserAction
{
    public function execute(array $data): User
    {
        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'status' => 'PENDING_VERIFICATION',
        ]);
    }
}
