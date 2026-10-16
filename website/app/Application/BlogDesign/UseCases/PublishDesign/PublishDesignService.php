<?php

declare(strict_types=1);

namespace App\Application\BlogDesign\UseCases\PublishDesign;

use App\Application\BlogDesign\Contracts\BlogDesignRepository;
use App\Application\BlogDesign\Services\OwnedBlogGuard;
use DomainException;

final readonly class PublishDesignService
{
    public function __construct(
        private OwnedBlogGuard $guard,
        private BlogDesignRepository $designs,
    ) {}

    public function execute(int $actorId, int $blogId): void
    {
        $this->guard->ensure($actorId, $blogId);

        if ($this->designs->find($blogId)?->draftData === null) {
            throw new DomainException('There is no draft design to publish.');
        }

        $this->designs->publishDraft($blogId, $actorId);
    }
}
