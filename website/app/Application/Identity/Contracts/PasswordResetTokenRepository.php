<?php

declare(strict_types=1);

namespace App\Application\Identity\Contracts;

use App\Domain\Identity\ValueObjects\EmailAddress;
use DateTimeImmutable;

interface PasswordResetTokenRepository
{
    /**
     * Store the token hash for the email, replacing any previous token.
     */
    public function save(EmailAddress $email, string $tokenHash, DateTimeImmutable $createdAt): void;

    /**
     * @return array{token_hash: string, created_at: DateTimeImmutable}|null
     */
    public function find(EmailAddress $email): ?array;

    public function delete(EmailAddress $email): void;
}
