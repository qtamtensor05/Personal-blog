<?php

declare(strict_types=1);

namespace App\Application\Identity\Contracts;

interface VerificationUrlGenerator
{
    public function generate(int $userId): string;
}
