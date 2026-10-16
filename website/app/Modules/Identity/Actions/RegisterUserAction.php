<?php

namespace App\Modules\Identity\Actions;

use App\Models\User;
use Illuminate\Auth\Events\Registered;

final class RegisterUserAction
{
    public function __construct(
        private readonly CreatePendingUserAction $createUser,
    ) {}

    public function execute(array $data): User
    {
        $user = $this->createUser->execute($data);

        event(new Registered($user));

        return $user;
    }
}
