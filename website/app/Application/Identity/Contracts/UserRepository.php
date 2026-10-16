<?php

declare(strict_types=1);

namespace App\Application\Identity\Contracts;

use App\Domain\Identity\Entities\User;
use App\Domain\Identity\ValueObjects\EmailAddress;

interface UserRepository
{
    public function findById(int $id): ?User;

    public function findByEmail(EmailAddress $email): ?User;

    public function create(string $name, EmailAddress $email, string $passwordHash): User;

    public function update(User $user): void;

    /**
     * Store a new password hash and sign the user out of every session.
     */
    public function changePassword(int $userId, string $passwordHash): void;
}
