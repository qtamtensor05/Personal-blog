<?php

declare(strict_types=1);

namespace App\Application\Administration\UseCases\ManageUsers;

use App\Application\Administration\Contracts\AdminUserRepository;
use App\Application\Administration\Contracts\AuditLogger;
use App\Application\Administration\DTOs\AdminUserRecord;
use App\Domain\Identity\Enums\UserStatus;
use DateTimeImmutable;
use DomainException;

/**
 * Admin actions on accounts. Every change writes admin_audit_logs.
 * Admins cannot act on their own account, so an admin can never lock themselves
 * out or remove the last role that lets someone manage roles.
 */
final readonly class ManageUsersService
{
    /** account_restrictions.kind => resulting users.status */
    private const KIND_STATUS = [
        'LOCK' => UserStatus::Locked,
        'SUSPEND' => UserStatus::Suspended,
        'DISABLE' => UserStatus::Disabled,
    ];

    public function __construct(
        private AdminUserRepository $users,
        private AuditLogger $audit,
    ) {}

    public function restrict(int $actorId, int $userId, string $kind, string $reason, ?DateTimeImmutable $endsAt): void
    {
        $user = $this->target($actorId, $userId);
        $status = self::KIND_STATUS[$kind] ?? throw new DomainException('Unknown restriction type.');

        if ($endsAt !== null && $endsAt <= new DateTimeImmutable) {
            throw new DomainException('The end date must be in the future.');
        }

        // A disabled account has no end date.
        $endsAt = $kind === 'DISABLE' ? null : $endsAt;

        $this->users->restrict($userId, $kind, $status, $reason, $actorId, $endsAt);

        $this->audit->log($actorId, 'user.restricted', 'user', (string) $userId,
            ['status' => $user->status->value],
            ['status' => $status->value, 'kind' => $kind, 'reason' => $reason, 'ends_at' => $endsAt?->format(DATE_ATOM)],
        );
    }

    public function lift(int $actorId, int $userId, string $reason): void
    {
        $user = $this->target($actorId, $userId);

        if (! in_array($user->status, [UserStatus::Locked, UserStatus::Suspended, UserStatus::Disabled], true)) {
            throw new DomainException('This account is not restricted.');
        }

        // Restore the status the account would have without the restriction.
        $status = $user->emailVerifiedAt !== null ? UserStatus::Active : UserStatus::PendingVerification;

        $this->users->liftRestrictions($userId, $status, $actorId, $reason);

        $this->audit->log($actorId, 'user.restriction_lifted', 'user', (string) $userId,
            ['status' => $user->status->value],
            ['status' => $status->value, 'reason' => $reason],
        );
    }

    public function grantRole(int $actorId, int $userId, int $roleId, ?DateTimeImmutable $expiresAt): void
    {
        $user = $this->target($actorId, $userId);

        if (! $this->users->roleExists($roleId)) {
            throw new DomainException('Role not found.');
        }

        if ($user->status !== UserStatus::Active) {
            throw new DomainException('Roles can only be granted to active accounts.');
        }

        if ($expiresAt !== null && $expiresAt <= new DateTimeImmutable) {
            throw new DomainException('The expiry date must be in the future.');
        }

        $this->users->grantRole($userId, $roleId, $actorId, $expiresAt);

        $this->audit->log($actorId, 'user.role_granted', 'user', (string) $userId, null,
            ['role_id' => $roleId, 'expires_at' => $expiresAt?->format(DATE_ATOM)],
        );
    }

    public function revokeRole(int $actorId, int $userId, int $roleId): void
    {
        $user = $this->target($actorId, $userId);

        if (! in_array($roleId, array_column($user->roles, 'role_id'), true)) {
            throw new DomainException('The user does not have this role.');
        }

        $this->users->revokeRole($userId, $roleId);

        $this->audit->log($actorId, 'user.role_revoked', 'user', (string) $userId, ['role_id' => $roleId], null);
    }

    private function target(int $actorId, int $userId): AdminUserRecord
    {
        if ($actorId === $userId) {
            throw new DomainException('You cannot change your own account from the Admin Console.');
        }

        return $this->users->find($userId) ?? throw new DomainException('User not found.');
    }
}
