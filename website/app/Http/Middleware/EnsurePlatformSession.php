<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsurePlatformSession
{
    public function handle(Request $request, Closure $next)
    {
        if ((int) $request->session()->get('platform_authenticated_user') !== (int) $request->user()->id) {
            auth()->logout();
            $request->session()->regenerate();

            return redirect('/platform-admin/login');
        }

        return $next($request);
    }
}
