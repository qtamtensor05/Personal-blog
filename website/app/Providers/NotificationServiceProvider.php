<?php

declare(strict_types=1);

namespace App\Providers;

use App\Application\Notifications\Contracts\NotificationPublisher;
use App\Application\Notifications\Contracts\NotificationRepository;
use App\Application\Notifications\UseCases\PublishNotificationService;
use App\Infrastructure\Notifications\QueryBuilderNotificationRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

final class NotificationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(NotificationRepository::class, QueryBuilderNotificationRepository::class);
        $this->app->bind(NotificationPublisher::class, PublishNotificationService::class);
    }

    public function boot(): void
    {
        View::composer('layouts.app', function ($view): void {
            $count = Auth::check()
                ? $this->app->make(NotificationRepository::class)->unreadCount((int) Auth::id())
                : 0;

            $view->with('notificationUnreadCount', $count);
        });
    }
}
