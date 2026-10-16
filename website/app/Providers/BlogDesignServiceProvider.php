<?php

declare(strict_types=1);

namespace App\Providers;

use App\Application\BlogDesign\Contracts\BlogDesignRepository;
use App\Infrastructure\BlogDesign\Persistence\QueryBuilderBlogDesignRepository;
use Illuminate\Support\ServiceProvider;

final class BlogDesignServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Use case services are concrete classes and autowired.
        $this->app->bind(BlogDesignRepository::class, QueryBuilderBlogDesignRepository::class);
    }
}
