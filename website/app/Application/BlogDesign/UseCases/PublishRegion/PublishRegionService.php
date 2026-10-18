<?php

declare(strict_types=1);

namespace App\Application\BlogDesign\UseCases\PublishRegion;

use App\Application\BlogDesign\Contracts\BlogDesignRepository;
use App\Application\BlogDesign\Services\OwnedBlogGuard;
use App\Domain\Templates\Enums\TemplateRegion;
use DomainException;

final readonly class PublishRegionService
{
    public function __construct(private OwnedBlogGuard $guard, private BlogDesignRepository $designs) {}

    public function execute(int $actorId, int $blogId, string $region): void
    {
        $this->guard->ensure($actorId, $blogId);

        if (TemplateRegion::tryFrom($region) === null) {
            throw new DomainException('Invalid template region.');
        }

        if ($this->designs->find($blogId)?->draftData === null) {
            throw new DomainException('There is no draft content to publish.');
        }

        $this->designs->publishRegion($blogId, $region, $actorId);
    }
}
