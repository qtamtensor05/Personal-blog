<?php

declare(strict_types=1);

namespace App\Application\Identity\Contracts;

interface PasswordResetEmailSender
{
    public function send(string $email, string $token): void;
}
