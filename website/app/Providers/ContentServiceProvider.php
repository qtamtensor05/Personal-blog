<?php

declare(strict_types=1);

namespace App\Providers;

use App\Application\Content\Contracts\LikeNotificationCooldownRepository;
use App\Application\Content\Contracts\LikeNotificationDispatcher;
use App\Application\Content\Contracts\PageRepository;
use App\Application\Content\Contracts\PostInteractionRepository;
use App\Application\Content\Contracts\PostRepository;
use App\Application\Content\Contracts\PublicPostCatalog;
use App\Application\Content\Contracts\TaxonomyRepository;
use App\Application\Content\UseCases\GetHomeContent\GetHomeContentService;
use App\Application\Content\UseCases\GetHomeContent\GetHomeContentUseCase;
use App\Infrastructure\Content\Jobs\QueueLikeNotificationDispatcher;
use App\Infrastructure\Content\Persistence\QueryBuilderLikeNotificationCooldownRepository;
use App\Infrastructure\Content\Persistence\QueryBuilderPageRepository;
use App\Infrastructure\Content\Persistence\QueryBuilderPostInteractionRepository;
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
        $this->app->bind(LikeNotificationCooldownRepository::class, QueryBuilderLikeNotificationCooldownRepository::class);
        $this->app->bind(LikeNotificationDispatcher::class, QueueLikeNotificationDispatcher::class);
        $this->app->bind(PublicPostCatalog::class, QueryBuilderPublicPostCatalog::class);
        $this->app->bind(PageRepository::class, QueryBuilderPageRepository::class);
        $this->app->bind(GetHomeContentUseCase::class, GetHomeContentService::class);
    }

    public function boot(): void
    {
        RateLimiter::for('post-interactions', fn (Request $request) => Limit::perMinute(30)
            ->by((string) $request->user()->getAuthIdentifier()));
    }
}
