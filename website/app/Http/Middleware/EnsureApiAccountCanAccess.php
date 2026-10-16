<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureApiAccountCanAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user('api');

        if ($user === null || ! $user->canAccessApplication()) {
            return response()->json([
                'message' => 'Tài khoản không được phép truy cập.',
            ], 403);
        }

        return $next($request);
    }
}
