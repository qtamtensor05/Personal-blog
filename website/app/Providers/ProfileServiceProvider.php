<?php

declare(strict_types=1);

namespace App\Providers;

use App\Application\Profile\Contracts\ProfileRepository;
use App\Infrastructure\Profile\QueryBuilderProfileRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

final class ProfileServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ProfileRepository::class, QueryBuilderProfileRepository::class);
    }

    public function boot(): void
    {
        View::composer('layouts.app', function ($view): void {
            $profile = Auth::check()
                ? $this->app->make(ProfileRepository::class)->find((int) Auth::id())
                : null;

            $view->with('profileAvatarUrl', $profile?->avatarUrl);
        });
    }
}
