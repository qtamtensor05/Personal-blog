<?php

declare(strict_types=1);

namespace App\Application\Identity\UseCases\IssueTokens;

use App\Application\Identity\DTOs\AuthTokens;

interface IssueTokensUseCase
{
    public function execute(
        string $email,
        string $password,
        ?string $ipAddress,
        ?string $userAgent,
    ): AuthTokens;
}
