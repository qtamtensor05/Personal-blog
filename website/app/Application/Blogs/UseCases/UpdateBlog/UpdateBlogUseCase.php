<?php

declare(strict_types=1);

namespace App\Application\Blogs\UseCases\UpdateBlog;

use App\Domain\Blogs\Enums\BlogStatus;
use App\Domain\Blogs\Enums\BlogVisibility;

interface UpdateBlogUseCase
{
    public function execute(
        int $actorId,
        int $blogId,
        string $name,
        string $slug,
        ?string $description,
        BlogStatus $status,
        BlogVisibility $visibility,
        bool $discoverable,
    ): void;
}
