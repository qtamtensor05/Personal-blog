<?php

namespace App\Modules\Identity\Services;

use App\Models\User;
use App\Modules\Identity\Contracts\TokenService;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

final class JwtTokenService implements TokenService
{
    public function issue(User $user): string
    {
        return JWTAuth::fromUser($user);
    }

    public function invalidateCurrentToken(): void
    {
        JWTAuth::parseToken()->invalidate();
    }

    public function refreshCurrentToken(): string
    {
        return JWTAuth::parseToken()->refresh();
    }
}
