<?php

declare(strict_types=1);

namespace App\Application\Identity\UseCases\SendVerificationEmail;

interface SendVerificationEmailUseCase
{
    public function execute(int $userId, string $verificationUrl): void;
}
