<?php

declare(strict_types=1);

namespace App\Application\Identity\UseCases\ResendVerificationEmail;

use App\Application\Identity\Contracts\UserRepository;
use App\Application\Identity\Contracts\VerificationUrlGenerator;
use App\Application\Identity\UseCases\SendVerificationEmail\SendVerificationEmailUseCase;
use App\Domain\Identity\Enums\UserStatus;
use App\Domain\Identity\ValueObjects\EmailAddress;

final readonly class ResendVerificationEmailService implements ResendVerificationEmailUseCase
{
    public function __construct(
        private UserRepository $users,
        private VerificationUrlGenerator $urlGenerator,
        private SendVerificationEmailUseCase $sendVerificationEmail,
    ) {}

    public function execute(string $email): void
    {
        $emailAddress = new EmailAddress($email);
        $user = $this->users->findByEmail($emailAddress);

        if (
            $user === null
            || $user->emailVerifiedAt() !== null
            || $user->status() !== UserStatus::PendingVerification
        ) {
            // Không tiết lộ email có tồn tại hay đã xác minh hay chưa.
            return;
        }

        $url = $this->urlGenerator->generate($user->id());

        $this->sendVerificationEmail->execute(
            $user->id(),
            $url,
        );
    }
}
