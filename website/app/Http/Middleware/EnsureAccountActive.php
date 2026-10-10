<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureAccountActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        abort_unless(
            $user && $user->canAccessApplication(),
            403,
            'Tài khoản chưa xác minh hoặc đang bị hạn chế.',
        );

        return $next($request);
    }
}
