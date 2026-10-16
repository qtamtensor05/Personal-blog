<?php

declare(strict_types=1);

namespace App\Application\Blogs\UseCases\CreateBlog;

use App\Application\Blogs\Contracts\BlogRepository;
use App\Application\Blogs\Exceptions\BlogSlugTaken;
use App\Domain\Blogs\Enums\BlogStatus;
use App\Domain\Blogs\Enums\BlogVisibility;

final readonly class CreateBlogService implements CreateBlogUseCase
{
    public function __construct(private BlogRepository $blogs) {}

    public function execute(
        int $ownerUserId,
        string $name,
        string $slug,
        ?string $description = null,
        BlogStatus $status = BlogStatus::Draft,
        BlogVisibility $visibility = BlogVisibility::Public,
        bool $discoverable = true,
    ): int {
        if ($this->blogs->slugExists($slug)) {
            throw new BlogSlugTaken;
        }

        return $this->blogs->create(
            ownerUserId: $ownerUserId,
            name: $name,
            slug: $slug,
            description: $description,
            status: $status,
            visibility: $visibility,
            discoverable: $discoverable,
        );
    }
}
