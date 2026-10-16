<?php

namespace App\Modules\Identity\Contracts;

use App\Models\User;

interface TokenService
{
    public function issue(User $user): string;

    public function invalidateCurrentToken(): void;

    public function refreshCurrentToken(): string;
}
