<?php

declare(strict_types=1);

namespace App\Providers;

use App\Application\Content\Contracts\LikeNotificationCooldownRepository;
use App\Application\Content\Contracts\LikeNotificationDispatcher;
use App\Application\Content\Contracts\PageRepository;
use App\Application\Content\Contracts\PostInteractionRepository;
use App\Application\Content\Contracts\PostLocationRepository;
use App\Application\Content\Contracts\PostRepository;
use App\Application\Content\Contracts\PublicPostCatalog;
use App\Application\Content\Contracts\TaxonomyRepository;
use App\Application\Content\UseCases\GetHomeContent\GetHomeContentService;
use App\Application\Content\UseCases\GetHomeContent\GetHomeContentUseCase;
use App\Application\Content\UseCases\ManagePostInteractions\PostEngagementService;
use App\Application\Content\UseCases\ManagePostInteractions\PostEngagementUseCase;
use App\Application\Content\UseCases\ManagePostInteractions\PostInteractionsService;
use App\Application\Content\UseCases\ManagePostInteractions\PostInteractionsUseCase;
use App\Application\Content\UseCases\ManagePostInteractions\PublishLikeNotificationService;
use App\Application\Content\UseCases\ManagePostInteractions\PublishLikeNotificationUseCase;
use App\Application\Content\UseCases\ManagePostInteractions\RecordPostViewService;
use App\Application\Content\UseCases\ManagePostInteractions\RecordPostViewUseCase;
use App\Application\Content\UseCases\ManagePostInteractions\ResolveLikedPostLocationsService;
use App\Application\Content\UseCases\ManagePostInteractions\ResolveLikedPostLocationsUseCase;
use App\Infrastructure\Content\Jobs\QueueLikeNotificationDispatcher;
use App\Infrastructure\Content\Persistence\QueryBuilderLikeNotificationCooldownRepository;
use App\Infrastructure\Content\Persistence\QueryBuilderPageRepository;
use App\Infrastructure\Content\Persistence\QueryBuilderPostInteractionRepository;
use App\Infrastructure\Content\Persistence\QueryBuilderPostLocationRepository;
use App\Infrastructure\Content\Persistence\QueryBuilderPostRepository;
use App\Infrastructure\Content\Persistence\QueryBuilderPublicPostCatalog;
use App\Infrastructure\Content\Persistence\QueryBuilderTaxonomyRepository;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

final class ContentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(TaxonomyRepository::class, QueryBuilderTaxonomyRepository::class);
        $this->app->bind(PostRepository::class, QueryBuilderPostRepository::class);
        $this->app->bind(PostInteractionRepository::class, QueryBuilderPostInteractionRepository::class);
        $this->app->bind(PostLocationRepository::class, QueryBuilderPostLocationRepository::class);
        $this->app->bind(LikeNotificationCooldownRepository::class, QueryBuilderLikeNotificationCooldownRepository::class);
        $this->app->bind(LikeNotificationDispatcher::class, QueueLikeNotificationDispatcher::class);
        $this->app->bind(PublicPostCatalog::class, QueryBuilderPublicPostCatalog::class);
        $this->app->bind(PageRepository::class, QueryBuilderPageRepository::class);
        $this->app->bind(GetHomeContentUseCase::class, GetHomeContentService::class);
        $this->app->bind(PostEngagementUseCase::class, PostEngagementService::class);
        $this->app->bind(PostInteractionsUseCase::class, PostInteractionsService::class);
        $this->app->bind(PublishLikeNotificationUseCase::class, PublishLikeNotificationService::class);
        $this->app->bind(RecordPostViewUseCase::class, RecordPostViewService::class);
        $this->app->bind(ResolveLikedPostLocationsUseCase::class, ResolveLikedPostLocationsService::class);
    }

    public function boot(): void
    {
        RateLimiter::for('post-interactions', fn (Request $request) => Limit::perMinute(30)
            ->by((string) $request->user()->getAuthIdentifier()));
    }
}
