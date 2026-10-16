<?php

declare(strict_types=1);

namespace App\Application\AccessControl\Services;

use App\Application\AccessControl\Contracts\PermissionRepository;
use App\Domain\AccessControl\Enums\PlatformPermission;

/**
 * Resolves a user's permissions once per request.
 * Bound as a scoped instance so the memo never outlives the request.
 */
final class PermissionResolver
{
    /** @var array<int, list<string>> */
    private array $cache = [];

    public function __construct(private readonly PermissionRepository $permissions) {}

    public function has(int $userId, PlatformPermission $permission): bool
    {
        $this->cache[$userId] ??= $this->permissions->codesForUser($userId);

        return in_array($permission->value, $this->cache[$userId], true);
    }
}
