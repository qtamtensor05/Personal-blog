<?php

declare(strict_types=1);

namespace App\Application\Profile\UseCases;

use App\Application\Identity\Contracts\PasswordHasher;
use App\Application\Profile\Contracts\ProfileRepository;
use DomainException;

final readonly class ChangeProfilePasswordService
{
    public function __construct(
        private ProfileRepository $profiles,
        private PasswordHasher $passwordHasher,
    ) {}

    public function execute(int $userId, string $currentPassword, string $newPassword): void
    {
        $this->assertCurrentPassword($userId, $currentPassword);

        if (strlen($newPassword) < 8) {
            throw new DomainException('Password must contain at least 8 characters.');
        }

        $this->profiles->updatePassword($userId, $this->passwordHasher->hash($newPassword));
    }

    public function assertCurrentPassword(int $userId, string $currentPassword): void
    {
        $currentHash = $this->profiles->passwordHash($userId);

        if (
            $currentHash === null ||
            ! $this->passwordHasher->verify($currentPassword, $currentHash)
        ) {
            throw new DomainException('The current password is incorrect.');
        }
    }
}
