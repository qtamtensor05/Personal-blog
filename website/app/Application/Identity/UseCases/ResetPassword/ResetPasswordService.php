<?php

declare(strict_types=1);

namespace App\Application\Identity\UseCases\ResetPassword;

use App\Application\Identity\Contracts\PasswordHasher;
use App\Application\Identity\Contracts\PasswordResetTokenRepository;
use App\Application\Identity\Contracts\UserRepository;
use App\Application\Identity\Exceptions\InvalidPasswordResetToken;
use App\Application\Identity\UseCases\RequestPasswordReset\RequestPasswordResetService;
use App\Domain\Identity\ValueObjects\EmailAddress;
use DateTimeImmutable;
use DomainException;

final readonly class ResetPasswordService implements ResetPasswordUseCase
{
    private const EXPIRE_MINUTES = 60;

    public function __construct(
        private UserRepository $users,
        private PasswordResetTokenRepository $tokens,
        private PasswordHasher $passwordHasher,
    ) {}

    public function execute(string $email, string $token, string $password): void
    {
        if (strlen($password) < 8) {
            throw new DomainException('Password must contain at least 8 characters.');
        }

        try {
            $emailAddress = new EmailAddress($email);
        } catch (DomainException) {
            throw new InvalidPasswordResetToken;
        }

        $record = $this->tokens->find($emailAddress);

        if ($record === null || ! hash_equals($record['token_hash'], hash('sha256', $token))) {
            throw new InvalidPasswordResetToken;
        }

        $expiresAt = $record['created_at']->modify('+'.self::EXPIRE_MINUTES.' minutes');

        if ($expiresAt <= new DateTimeImmutable) {
            $this->tokens->delete($emailAddress);

            throw new InvalidPasswordResetToken;
        }

        $user = $this->users->findByEmail($emailAddress);

        if (
            $user === null ||
            ! in_array($user->status(), RequestPasswordResetService::resettableStatuses(), true)
        ) {
            $this->tokens->delete($emailAddress);

            throw new InvalidPasswordResetToken;
        }

        $this->users->changePassword($user->id(), $this->passwordHasher->hash($password));
        $this->tokens->delete($emailAddress);
    }
}
