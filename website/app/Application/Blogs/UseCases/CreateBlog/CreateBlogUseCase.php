<?php

declare(strict_types=1);

namespace App\Application\Blogs\UseCases\CreateBlog;

use App\Domain\Blogs\Enums\BlogStatus;
use App\Domain\Blogs\Enums\BlogVisibility;

interface CreateBlogUseCase
{
    /**
     * Returns the new blog ID.
     */
    public function execute(
        int $ownerUserId,
        string $name,
        string $slug,
        ?string $description = null,
        BlogStatus $status = BlogStatus::Draft,
        BlogVisibility $visibility = BlogVisibility::Public,
        bool $discoverable = true,
    ): int;
}
