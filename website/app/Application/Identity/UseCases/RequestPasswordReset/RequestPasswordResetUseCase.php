<?php

declare(strict_types=1);

namespace App\Application\Identity\UseCases\RequestPasswordReset;

interface RequestPasswordResetUseCase
{
    public function execute(string $email): void;
}
