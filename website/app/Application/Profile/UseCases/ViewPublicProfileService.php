<?php

declare(strict_types=1);

namespace App\Application\Profile\UseCases;

use App\Application\Blogs\Contracts\BlogRepository;
use App\Application\Blogs\DTOs\BlogRecord;
use App\Application\Profile\Contracts\ProfileRepository;
use App\Application\Profile\DTOs\PublicProfileRecord;
use App\Domain\Blogs\Enums\BlogStatus;
use App\Domain\Blogs\Enums\BlogVisibility;

final readonly class ViewPublicProfileService
{
    public function __construct(
        private ProfileRepository $profiles,
        private BlogRepository $blogs,
    ) {}

    /** @return array{profile: PublicProfileRecord, blogs: list<BlogRecord>}|null */
    public function execute(int $userId): ?array
    {
        $profile = $this->profiles->findPublic($userId);
        if ($profile === null) {
            return null;
        }

        $blogs = array_values(array_filter(
            $this->blogs->ownedBy($userId),
            fn (BlogRecord $blog): bool => $blog->status === BlogStatus::Published
                && $blog->visibility === BlogVisibility::Public
                && $blog->discoverable,
        ));

        return ['profile' => $profile, 'blogs' => $blogs];
    }
}
