<?php

declare(strict_types=1);

namespace App\Application\Identity\UseCases\ResendVerificationEmail;

interface ResendVerificationEmailUseCase
{
    public function execute(string $email): void;
}
