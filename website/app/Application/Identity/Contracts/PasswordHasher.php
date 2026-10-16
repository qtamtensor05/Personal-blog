<?php

declare(strict_types=1);

namespace App\Application\Identity\Contracts;

interface PasswordHasher
{
    public function hash(string $plainText): string;
}
