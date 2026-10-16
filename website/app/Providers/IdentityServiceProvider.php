<?php

declare(strict_types=1);

namespace App\Providers;

use App\Application\Identity\Contracts\PasswordHasher;
use App\Application\Identity\Contracts\UserRepository;
use App\Application\Identity\UseCases\RegisterUser\RegisterUserService;
use App\Application\Identity\UseCases\RegisterUser\RegisterUserUseCase;
use App\Infrastructure\Identity\Persistence\QueryBuilderUserRepository;
use App\Infrastructure\Security\LaravelPasswordHasher;
use Illuminate\Support\ServiceProvider;

final class IdentityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            UserRepository::class,
            QueryBuilderUserRepository::class,
        );

        $this->app->bind(
            PasswordHasher::class,
            LaravelPasswordHasher::class,
        );

        $this->app->bind(
            RegisterUserUseCase::class,
            RegisterUserService::class,
        );
    }
}
