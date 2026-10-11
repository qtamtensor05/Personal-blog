<?php

namespace App\Filament\Auth;

use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login;

class PlatformLogin extends Login
{
    public function authenticate(): ?LoginResponse
    {
        $response = parent::authenticate();
        if ($response) {
            session()->put('platform_authenticated_user', (int) auth()->id());
        }

        return $response;
    }
}
