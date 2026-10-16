<?php

declare(strict_types=1);

namespace App\Application\Blogs\UseCases\UpdateBlog;

use App\Application\Blogs\Contracts\BlogRepository;
use App\Application\Blogs\Exceptions\BlogNotFound;
use App\Application\Blogs\Exceptions\BlogSlugTaken;
use App\Domain\Blogs\Enums\BlogStatus;
use App\Domain\Blogs\Enums\BlogVisibility;
use DomainException;

final readonly class UpdateBlogService implements UpdateBlogUseCase
{
    public function __construct(private BlogRepository $blogs) {}

    public function execute(
        int $actorId,
        int $blogId,
        string $name,
        string $slug,
        ?string $description,
        BlogStatus $status,
        BlogVisibility $visibility,
        bool $discoverable,
    ): void {
        $blog = $this->blogs->find($blogId);

        if ($blog === null) {
            throw new BlogNotFound;
        }

        if ($blog->ownerUserId !== $actorId) {
            throw new DomainException('You do not own this blog.');
        }

        // SUSPENDED is set by moderation; the owner can neither set nor lift it.
        if ($blog->status === BlogStatus::Suspended || $status === BlogStatus::Suspended) {
            throw new DomainException('This blog is suspended and cannot be changed.');
        }

        if ($slug !== $blog->slug && $this->blogs->slugExists($slug, $blogId)) {
            throw new BlogSlugTaken;
        }

        $this->blogs->update(
            id: $blogId,
            name: $name,
            slug: $slug,
            description: $description,
            status: $status,
            visibility: $visibility,
            discoverable: $discoverable,
        );
    }
}
