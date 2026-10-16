<?php

declare(strict_types=1);

namespace App\Providers;

use App\Application\BlogDesign\Services\OwnedBlogGuard;
use App\Application\Domains\Contracts\BlogDomainRepository;
use App\Application\Domains\Contracts\DnsTxtResolver;
use App\Application\Domains\UseCases\ManageDomains\ManageDomainsService;
use App\Infrastructure\Domains\PhpDnsTxtResolver;
use App\Infrastructure\Domains\QueryBuilderBlogDomainRepository;
use Illuminate\Support\ServiceProvider;

final class DomainServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(BlogDomainRepository::class, QueryBuilderBlogDomainRepository::class);
        $this->app->bind(DnsTxtResolver::class, PhpDnsTxtResolver::class);

        $this->app->bind(ManageDomainsService::class, fn ($app) => new ManageDomainsService(
            guard: $app->make(OwnedBlogGuard::class),
            domains: $app->make(BlogDomainRepository::class),
            dns: $app->make(DnsTxtResolver::class),
            secret: (string) config('app.key'),
            platformHost: self::platformHost(),
        ));
    }

    public static function platformHost(): string
    {
        return strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST));
    }
}
