<?php

declare(strict_types=1);

namespace App\Application\Media\Services;

use App\Application\AccessControl\Services\PermissionResolver;
use App\Application\Media\Contracts\BlogOwnership;
use App\Application\Media\Exceptions\MediaAccessDenied;
use App\Domain\AccessControl\Enums\PlatformPermission;
use App\Domain\Media\Enums\MediaScope;

/**
 * Who may upload into or delete from a media scope:
 * - USER: the user themself (uploader)
 * - BLOG: the blog owner (schema has no blog collaborators)
 * - PLATFORM: admins with templates.manage
 */
final readonly class MediaAccessPolicy
{
    public function __construct(
        private BlogOwnership $blogs,
        private PermissionResolver $permissions,
    ) {}

    public function authorize(
        int $actorId,
        MediaScope $scope,
        ?int $blogId,
        ?int $uploadedBy = null,
    ): void {
        $allowed = match ($scope) {
            MediaScope::User => $uploadedBy === null || $uploadedBy === $actorId,
            MediaScope::Blog => $blogId !== null && $this->blogs->ownerOf($blogId) === $actorId,
            MediaScope::Platform => $this->permissions->has(
                $actorId,
                PlatformPermission::ManageTemplates,
            ),
        };

        if (! $allowed) {
            throw new MediaAccessDenied;
        }
    }
}
