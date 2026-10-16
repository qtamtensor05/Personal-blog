<?php

declare(strict_types=1);

namespace App\Application\Identity\UseCases\RegisterUser;

use App\Domain\Identity\Entities\User;

interface RegisterUserUseCase
{
    public function execute(string $name, string $email, string $password): User;
}
