<?php

declare(strict_types=1);

namespace App\Application\Administration\UseCases\ManageUsers;

use App\Application\Administration\Contracts\AdminUserRepository;
use App\Application\Administration\Contracts\AuditLogger;
use App\Application\Administration\DTOs\AdminUserRecord;
use App\Application\Notifications\Contracts\NotificationPublisher;
use App\Domain\Identity\Enums\UserStatus;
use DateTimeImmutable;
use DomainException;

final readonly class ManageUsersService
{
    private const KIND_STATUS = [
        'LOCK' => UserStatus::Locked,
        'SUSPEND' => UserStatus::Suspended,
        'DISABLE' => UserStatus::Disabled,
    ];

    private const SUPER_ADMIN_ROLE = 'SUPER_ADMIN';

    public function __construct(
        private AdminUserRepository $users,
        private AuditLogger $audit,
        private NotificationPublisher $notifications,
    ) {}

    public function restrict(
        int $actorId,
        int $userId,
        string $kind,
        string $reason,
        ?DateTimeImmutable $endsAt,
    ): void {
        $user = $this->target($actorId, $userId);

        $status =
            self::KIND_STATUS[$kind] ?? throw new DomainException('Unknown restriction type.');

        if ($endsAt !== null && $endsAt <= new DateTimeImmutable) {
            throw new DomainException('The end date must be in the future.');
        }

        $endsAt = $kind === 'DISABLE' ? null : $endsAt;

        if (
            $this->users->userHasRole($userId, self::SUPER_ADMIN_ROLE) &&
            $this->users->countActiveSuperAdmins() <= 1
        ) {
            throw new DomainException('Cannot restrict the last active Super Admin.');
        }

        $this->users->restrict($userId, $kind, $status, $reason, $actorId, $endsAt);

        $this->audit->log(
            $actorId,
            'user.restricted',
            'user',
            (string) $userId,
            ['status' => $user->status->value],
            [
                'status' => $status->value,
                'kind' => $kind,
                'reason' => $reason,
                'ends_at' => $endsAt?->format(DATE_ATOM),
            ],
        );

        $this->notifications->publish($userId, 'account.restricted', [
            'title' => 'Account restriction applied',
            'message' => "A {$kind} restriction was applied to your account. Reason: {$reason}",
            'kind' => $kind,
            'ends_at' => $endsAt?->format(DATE_ATOM),
        ]);
    }

    public function lift(int $actorId, int $userId, string $reason): void
    {
        $user = $this->target($actorId, $userId);

        if (
            ! in_array(
                $user->status,
                [UserStatus::Locked, UserStatus::Suspended, UserStatus::Disabled],
                true,
            )
        ) {
            throw new DomainException('This account is not restricted.');
        }

        $status =
            $user->emailVerifiedAt !== null ? UserStatus::Active : UserStatus::PendingVerification;

        $this->users->liftRestrictions($userId, $status, $actorId, $reason);

        $this->audit->log(
            $actorId,
            'user.restriction_lifted',
            'user',
            (string) $userId,
            ['status' => $user->status->value],
            [
                'status' => $status->value,
                'reason' => $reason,
            ],
        );

        $this->notifications->publish($userId, 'account.restriction_lifted', [
            'title' => 'Account restriction lifted',
            'message' => "Your account restriction was lifted. Reason: {$reason}",
        ]);
    }

    public function grantRole(
        int $actorId,
        int $userId,
        int $roleId,
        ?DateTimeImmutable $expiresAt,
    ): void {
        $user = $this->target($actorId, $userId);

        if (! $this->users->roleExists($roleId)) {
            throw new DomainException('Role not found.');
        }

        $roleCode = $this->users->roleCode($roleId);

        if (
            $roleCode === self::SUPER_ADMIN_ROLE &&
            ! $this->users->userHasRole($actorId, self::SUPER_ADMIN_ROLE)
        ) {
            throw new DomainException('Only a Super Admin can grant the SUPER_ADMIN role.');
        }

        if ($user->status !== UserStatus::Active) {
            throw new DomainException('Roles can only be granted to active accounts.');
        }

        if ($expiresAt !== null && $expiresAt <= new DateTimeImmutable) {
            throw new DomainException('The expiry date must be in the future.');
        }

        $this->users->grantRole($userId, $roleId, $actorId, $expiresAt);

        $this->audit->log($actorId, 'user.role_granted', 'user', (string) $userId, null, [
            'role_id' => $roleId,
            'role_code' => $roleCode,
            'expires_at' => $expiresAt?->format(DATE_ATOM),
        ]);

        $this->notifications->publish($userId, 'role.granted', [
            'title' => 'Platform role granted',
            'message' => "Role #{$roleId} was granted to your account.",
            'role_id' => $roleId,
            'expires_at' => $expiresAt?->format(DATE_ATOM),
        ]);
    }

    public function revokeRole(int $actorId, int $userId, int $roleId): void
    {
        $user = $this->target($actorId, $userId);

        if (
            ! in_array(
                $roleId,
                array_map(static fn (array $role): int => (int) $role['role_id'], $user->roles),
                true,
            )
        ) {
            throw new DomainException('The user does not have this role.');
        }

        $roleCode = $this->users->roleCode($roleId);

        if ($roleCode === null) {
            throw new DomainException('Role not found.');
        }

        if (
            $roleCode === self::SUPER_ADMIN_ROLE &&
            ! $this->users->userHasRole($actorId, self::SUPER_ADMIN_ROLE)
        ) {
            throw new DomainException('Only a Super Admin can revoke the SUPER_ADMIN role.');
        }

        if ($roleCode === self::SUPER_ADMIN_ROLE && $this->users->countActiveSuperAdmins() <= 1) {
            throw new DomainException('Cannot revoke the last active Super Admin.');
        }

        $this->users->revokeRole($userId, $roleId);

        $this->audit->log(
            $actorId,
            'user.role_revoked',
            'user',
            (string) $userId,
            [
                'role_id' => $roleId,
                'role_code' => $roleCode,
            ],
            null,
        );

        $this->notifications->publish($userId, 'role.revoked', [
            'title' => 'Platform role revoked',
            'message' => "Role #{$roleId} was removed from your account.",
            'role_id' => $roleId,
        ]);
    }

    public function setPermissionOverride(
        int $actorId,
        int $userId,
        int $permissionId,
        string $effect,
    ): void {
        $user = $this->target($actorId, $userId);

        if (! in_array($effect, ['GRANT', 'DENY'], true)) {
            throw new DomainException('Unknown permission effect.');
        }

        if (! $this->users->userHasRole($actorId, self::SUPER_ADMIN_ROLE)) {
            throw new DomainException('Only a Super Admin can change individual permissions.');
        }

        if (! $this->users->permissionExists($permissionId)) {
            throw new DomainException('Permission not found.');
        }

        $permissionCode = $this->users->permissionCode($permissionId);

        $this->users->setPermissionOverride($userId, $permissionId, $effect, $actorId);

        $this->audit->log(
            $actorId,
            $effect === 'GRANT' ? 'user.permission_granted' : 'user.permission_denied',
            'user',
            (string) $user->id,
            null,
            ['permission_id' => $permissionId, 'permission_code' => $permissionCode],
        );

        $this->notifications->publish($userId, 'permission.updated', [
            'title' => 'Permission updated',
            'message' => "Permission {$permissionCode} was updated for your account.",
            'permission_code' => $permissionCode,
            'effect' => $effect,
        ]);
    }

    private function target(int $actorId, int $userId): AdminUserRecord
    {
        if ($actorId === $userId) {
            throw new DomainException('You cannot change your own account from the Admin Console.');
        }

        return $this->users->find($userId) ?? throw new DomainException('User not found.');
    }
}
