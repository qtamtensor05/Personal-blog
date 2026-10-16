<?php

declare(strict_types=1);

namespace App\Application\Identity\UseCases\RegisterUser;

use App\Application\Identity\Contracts\PasswordHasher;
use App\Application\Identity\Contracts\UserRepository;
use App\Application\Identity\Exceptions\EmailAlreadyRegistered;
use App\Domain\Identity\Entities\User;
use App\Domain\Identity\ValueObjects\EmailAddress;
use DomainException;

final readonly class RegisterUserService implements RegisterUserUseCase
{
    public function __construct(
        private UserRepository $users,
        private PasswordHasher $passwordHasher,
    ) {}

    public function execute(
        string $name,
        string $email,
        string $password,
    ): User {
        $name = trim($name);

        if ($name === '' || mb_strlen($name) > 160) {
            throw new DomainException(
                'Name must contain between 1 and 160 characters.'
            );
        }

        $emailAddress = new EmailAddress($email);

        if ($this->users->findByEmail($emailAddress) !== null) {
            throw new EmailAlreadyRegistered;
        }

        if (strlen($password) < 8) {
            throw new DomainException(
                'Password must contain at least 8 characters.'
            );
        }

        return $this->users->create(
            name: $name,
            email: $emailAddress,
            passwordHash: $this->passwordHasher->hash($password),
        );
    }
}
