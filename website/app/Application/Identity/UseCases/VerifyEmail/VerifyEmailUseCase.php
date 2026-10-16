<?php

declare(strict_types=1);

namespace App\Application\Identity\UseCases\VerifyEmail;

interface VerifyEmailUseCase
{
    public function execute(int $userId): void;
}
