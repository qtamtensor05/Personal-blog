<?php

namespace App\Modules\Identity\Auth;

use Illuminate\Contracts\Auth\Authenticatable;

final class SessionStamp
{
    public static function value(Authenticatable $user): string
    {
        return hash_hmac('sha256', json_encode([$user->getAuthIdentifier(), $user->getAuthPassword(), $user->getRememberToken()], JSON_THROW_ON_ERROR), config('app.key'));
    }

    public static function bind(Authenticatable $user): void
    {
        session()->put('identity_stamp', self::value($user));
    }
}
