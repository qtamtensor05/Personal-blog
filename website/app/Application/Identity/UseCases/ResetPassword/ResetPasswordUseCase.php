<?php

declare(strict_types=1);

namespace App\Application\Identity\UseCases\ResetPassword;

interface ResetPasswordUseCase
{
    public function execute(string $email, string $token, string $password): void;
}
