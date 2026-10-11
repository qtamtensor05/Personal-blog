<?php

namespace App\Http\Middleware;

use App\Modules\Identity\Auth\SessionStamp;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;

final class EnsureCurrentSession
{
    public function handle(Request $request, Closure $next)
    {
        if (! $this->valid($request)) {
            return $this->reject($request);
        }
        $response = $next($request);

        // A loaded session may outlive a revocation while its request is running.
        return $this->valid($request) ? $response : $this->reject($request);
    }

    private function valid(Request $request): bool
    {
        $user = $request->user();
        if (! $user) {
            return true;
        }
        $fresh = $user->newQuery()->whereKey($user->getAuthIdentifier())->first();

        return $fresh && $fresh->getAttribute('deleted_at') === null && hash_equals(SessionStamp::value($fresh), (string) $request->session()->get('identity_stamp', ''));
    }

    private function reject(Request $request)
    {
        Auth::guard()->forgetUser();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        Cookie::queue(Cookie::forget(Auth::guard()->getRecallerName()));

        return $request->expectsJson() ? response()->json(['message' => 'Phiên đã bị thu hồi. Vui lòng đăng nhập lại.'], 401) : redirect('/login')->with('status', 'Phiên đã bị thu hồi. Vui lòng đăng nhập lại.');
    }
}
