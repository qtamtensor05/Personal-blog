<?php

declare(strict_types=1);

namespace App\Application\Blogs\UseCases\DeleteBlog;

use App\Application\Blogs\Contracts\BlogRepository;
use App\Application\Blogs\Exceptions\BlogNotFound;
use DomainException;

final readonly class DeleteBlogService implements DeleteBlogUseCase
{
    public function __construct(private BlogRepository $blogs) {}

    public function execute(int $actorId, int $blogId): void
    {
        $blog = $this->blogs->find($blogId);

        if ($blog === null) {
            throw new BlogNotFound;
        }

        if ($blog->ownerUserId !== $actorId) {
            throw new DomainException('You do not own this blog.');
        }

        $this->blogs->softDelete($blogId);
    }
}
