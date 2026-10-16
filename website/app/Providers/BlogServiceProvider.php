<?php

declare(strict_types=1);

namespace App\Providers;

use App\Application\Blogs\Contracts\BlogRepository;
use App\Application\Blogs\UseCases\CreateBlog\CreateBlogService;
use App\Application\Blogs\UseCases\CreateBlog\CreateBlogUseCase;
use App\Application\Blogs\UseCases\DeleteBlog\DeleteBlogService;
use App\Application\Blogs\UseCases\DeleteBlog\DeleteBlogUseCase;
use App\Application\Blogs\UseCases\UpdateBlog\UpdateBlogService;
use App\Application\Blogs\UseCases\UpdateBlog\UpdateBlogUseCase;
use App\Infrastructure\Blogs\Persistence\QueryBuilderBlogRepository;
use Illuminate\Support\ServiceProvider;

final class BlogServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(BlogRepository::class, QueryBuilderBlogRepository::class);

        $this->app->bind(CreateBlogUseCase::class, CreateBlogService::class);
        $this->app->bind(UpdateBlogUseCase::class, UpdateBlogService::class);
        $this->app->bind(DeleteBlogUseCase::class, DeleteBlogService::class);
    }
}
