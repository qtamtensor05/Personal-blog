<?php

declare(strict_types=1);

namespace App\Providers;

use App\Application\Content\Contracts\PageRepository;
use App\Application\Content\Contracts\PostRepository;
use App\Application\Content\Contracts\PublicPostCatalog;
use App\Application\Content\Contracts\TaxonomyRepository;
use App\Infrastructure\Content\Persistence\QueryBuilderPageRepository;
use App\Infrastructure\Content\Persistence\QueryBuilderPostRepository;
use App\Infrastructure\Content\Persistence\QueryBuilderPublicPostCatalog;
use App\Infrastructure\Content\Persistence\QueryBuilderTaxonomyRepository;
use Illuminate\Support\ServiceProvider;

final class ContentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(TaxonomyRepository::class, QueryBuilderTaxonomyRepository::class);
        $this->app->bind(PostRepository::class, QueryBuilderPostRepository::class);
        $this->app->bind(PublicPostCatalog::class, QueryBuilderPublicPostCatalog::class);
        $this->app->bind(PageRepository::class, QueryBuilderPageRepository::class);
    }
}
