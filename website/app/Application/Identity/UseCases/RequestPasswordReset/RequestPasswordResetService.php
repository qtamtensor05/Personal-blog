<?php

declare(strict_types=1);

namespace App\Application\Identity\UseCases\RequestPasswordReset;

use App\Application\Identity\Contracts\PasswordResetEmailSender;
use App\Application\Identity\Contracts\PasswordResetTokenRepository;
use App\Application\Identity\Contracts\UserRepository;
use App\Domain\Identity\Enums\UserStatus;
use App\Domain\Identity\ValueObjects\EmailAddress;
use DateTimeImmutable;

final readonly class RequestPasswordResetService implements RequestPasswordResetUseCase
{
    // Minimum gap between two reset emails for the same address.
    private const THROTTLE_SECONDS = 60;

    public function __construct(
        private UserRepository $users,
        private PasswordResetTokenRepository $tokens,
        private PasswordResetEmailSender $sender,
    ) {}

    public function execute(string $email): void
    {
        $emailAddress = new EmailAddress($email);
        $user = $this->users->findByEmail($emailAddress);

        // Không tiết lộ email có tồn tại hay tài khoản có bị khóa hay không.
        if ($user === null || ! in_array($user->status(), self::resettableStatuses(), true)) {
            return;
        }

        $now = new DateTimeImmutable;
        $existing = $this->tokens->find($emailAddress);

        if (
            $existing !== null &&
            $now->getTimestamp() - $existing['created_at']->getTimestamp() < self::THROTTLE_SECONDS
        ) {
            return;
        }

        $token = bin2hex(random_bytes(32));

        // Only the hash is stored, so a database leak does not expose usable links.
        $this->tokens->save($emailAddress, hash('sha256', $token), $now);
        $this->sender->send($user->email()->value(), $token);
    }

    /**
     * Locked, suspended and disabled accounts must not regain access through a reset.
     *
     * @return list<UserStatus>
     */
    public static function resettableStatuses(): array
    {
        return [UserStatus::Active, UserStatus::PendingVerification];
    }
}
