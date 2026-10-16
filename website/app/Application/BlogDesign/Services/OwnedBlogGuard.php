<?php

declare(strict_types=1);

namespace App\Application\BlogDesign\Services;

use App\Application\Blogs\Contracts\BlogRepository;
use App\Application\Blogs\DTOs\BlogRecord;
use App\Application\Blogs\Exceptions\BlogNotFound;

/**
 * Schema has no blog collaborators, so only the owner may edit a blog's design.
 */
final readonly class OwnedBlogGuard
{
    public function __construct(private BlogRepository $blogs) {}

    public function ensure(int $actorId, int $blogId): BlogRecord
    {
        $blog = $this->blogs->find($blogId);

        // Same answer for "missing" and "not yours".
        if ($blog === null || $blog->ownerUserId !== $actorId) {
            throw new BlogNotFound;
        }

        return $blog;
    }
}
