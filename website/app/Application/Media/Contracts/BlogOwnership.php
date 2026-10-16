<?php

declare(strict_types=1);

namespace App\Application\Media\Contracts;

/**
 * Narrow port so Media does not depend on the future Blog module.
 */
interface BlogOwnership
{
    /**
     * Owner user ID of a non-deleted blog, or null when the blog does not exist.
     */
    public function ownerOf(int $blogId): ?int;
}
