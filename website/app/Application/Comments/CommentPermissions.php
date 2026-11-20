<?php

declare(strict_types=1);

namespace App\Application\Comments;

use App\Application\AccessControl\Services\PermissionResolver;
use App\Domain\AccessControl\Enums\PlatformPermission;

final readonly class CommentPermissions
{
    public function __construct(private PermissionResolver $permissions) {}

    public function isModerator(?object $actor): bool
    {
        return $actor !== null && $actor->status === 'ACTIVE'
            && $this->permissions->has((int) $actor->id, PlatformPermission::ModerateContent);
    }

    public function isPostOwner(?object $actor, int $ownerId): bool
    {
        return $actor !== null && (int) $actor->id === $ownerId;
    }

    public function mayWrite(?object $actor): bool
    {
        return $actor !== null && $actor->status === 'ACTIVE' && $actor->email_verified_at !== null;
    }
}
