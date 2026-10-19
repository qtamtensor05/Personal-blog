<?php

declare(strict_types=1);

namespace App\Presentation\Web\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class SetInterfaceLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->session()->get('interface_locale', config('app.locale'));
        app()->setLocale(in_array($locale, ['vi', 'en'], true) ? $locale : 'vi');

        return $next($request);
    }
}
