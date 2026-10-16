<?php

declare(strict_types=1);

namespace App\Domain\Identity\Entities;

use App\Domain\Identity\Enums\UserStatus;
use App\Domain\Identity\ValueObjects\EmailAddress;
use DateTimeImmutable;
use DomainException;

final class User
{
    public function __construct(
        private readonly int $id,
        private string $name,
        private readonly EmailAddress $email,
        private UserStatus $status,
        private ?DateTimeImmutable $emailVerifiedAt = null,
    ) {
        if ($this->id < 1) {
            throw new DomainException('User ID must be positive.');
        }

        $this->rename($name);
    }

    public function id(): int
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function email(): EmailAddress
    {
        return $this->email;
    }

    public function status(): UserStatus
    {
        return $this->status;
    }

    public function emailVerifiedAt(): ?DateTimeImmutable
    {
        return $this->emailVerifiedAt;
    }

    public function rename(string $name): void
    {
        $name = trim($name);

        if ($name === '' || mb_strlen($name) > 160) {
            throw new DomainException(
                'Name must contain between 1 and 160 characters.'
            );
        }

        $this->name = $name;
    }

    public function verifyEmail(DateTimeImmutable $verifiedAt): void
    {
        $this->emailVerifiedAt = $verifiedAt;

        if ($this->status === UserStatus::PendingVerification) {
            $this->status = UserStatus::Active;
        }
    }

    public function isActive(): bool
    {
        return $this->status === UserStatus::Active;
    }
}
