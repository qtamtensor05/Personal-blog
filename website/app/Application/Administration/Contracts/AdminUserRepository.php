<?php

declare(strict_types=1);

namespace App\Application\Administration\Contracts;

use App\Application\Administration\DTOs\AdminUserRecord;
use App\Domain\Identity\Enums\UserStatus;
use DateTimeImmutable;

interface AdminUserRepository
{
    /**
     * @return array{items: list<AdminUserRecord>, total: int}
     */
    public function search(?string $query, ?UserStatus $status, int $page, int $perPage): array;

    /**
     * Includes roles and active restrictions.
     */
    public function find(int $id): ?AdminUserRecord;

    /**
     * @return list<array{id: int, code: string, name: string}>
     */
    public function roles(): array;

    public function roleExists(int $roleId): bool;

    /**
     * Records the restriction, updates users.status (and locked_until for LOCK)
     * and signs the user out of every session, in one transaction.
     */
    public function restrict(
        int $userId,
        string $kind,
        UserStatus $status,
        string $reason,
        int $issuedBy,
        ?DateTimeImmutable $endsAt,
    ): void;

    /**
     * Revokes all ACTIVE restrictions and sets the given status.
     */
    public function liftRestrictions(int $userId, UserStatus $status, int $revokedBy, string $reason): void;

    public function grantRole(int $userId, int $roleId, int $grantedBy, ?DateTimeImmutable $expiresAt): void;

    public function revokeRole(int $userId, int $roleId): void;

    /**
     * Latest admin audit entries, newest first.
     *
     * @return array{items: list<object>, total: int}
     */
    public function auditLogs(int $page, int $perPage): array;
}
