<?php

declare(strict_types=1);

namespace App\Application\Identity\UseCases\VerifyEmail;

use App\Application\Identity\Contracts\UserRepository;
use App\Domain\Identity\Enums\UserStatus;
use DateTimeImmutable;
use DomainException;

final readonly class VerifyEmailService implements VerifyEmailUseCase
{
    public function __construct(
        private UserRepository $users,
    ) {}

    public function execute(int $userId): void
    {
        $user = $this->users->findById($userId);

        if ($user === null) {
            throw new DomainException('User not found.');
        }

        if ($user->emailVerifiedAt() !== null) {
            return;
        }

        if ($user->status() !== UserStatus::PendingVerification) {
            throw new DomainException(
                'This account cannot be verified.'
            );
        }

        $user->verifyEmail(new DateTimeImmutable);

        $this->users->update($user);
    }
}
