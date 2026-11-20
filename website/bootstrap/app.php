<?php

use App\Presentation\Web\Middleware\ServeCustomDomain;
use App\Presentation\Web\Middleware\SetInterfaceLocale;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        channels: __DIR__.'/../routes/channels.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('account:expire-restrictions')->everyMinute();
    })
    ->withMiddleware(function (Middleware $middleware): void {
        // Before routing: answers requests for ACTIVE custom blog domains.
        $middleware->prepend(ServeCustomDomain::class);
        $middleware->web(append: [SetInterfaceLocale::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
