<?php

namespace App\Modules\Identity\Contracts;

use App\Models\User;

interface UserRepository
{
    public function findByEmailForAuthentication(string $email): ?User;

    public function recordSuccessfulLogin(int $userId): void;
}
