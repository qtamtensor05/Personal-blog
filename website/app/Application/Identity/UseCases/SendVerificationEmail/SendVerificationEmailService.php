<?php

declare(strict_types=1);

namespace App\Application\Identity\UseCases\SendVerificationEmail;

use App\Application\Identity\Contracts\UserRepository;
use App\Application\Identity\Contracts\VerificationEmailSender;
use DomainException;

final readonly class SendVerificationEmailService implements SendVerificationEmailUseCase
{
    public function __construct(
        private UserRepository $users,
        private VerificationEmailSender $sender,
    ) {}

    public function execute(
        int $userId,
        string $verificationUrl,
    ): void {
        $user = $this->users->findById($userId);

        if ($user === null) {
            throw new DomainException('User not found.');
        }

        if ($user->emailVerifiedAt() !== null) {
            throw new DomainException('Email is already verified.');
        }

        $this->sender->send(
            $user->email()->value(),
            $verificationUrl,
        );
    }
}
