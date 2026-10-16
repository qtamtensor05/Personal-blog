<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Application\Identity\Contracts\PasswordHasher;
use Illuminate\Support\Facades\Hash;

final class LaravelPasswordHasher implements PasswordHasher
{
    public function hash(string $plainText): string
    {
        return Hash::make($plainText);
    }
}
