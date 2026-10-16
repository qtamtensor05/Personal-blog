<?php

declare(strict_types=1);

namespace App\Presentation\Web\Middleware;

use App\Application\AccessControl\Services\PermissionResolver;
use App\Domain\AccessControl\Enums\PlatformPermission;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Usage: ->middleware('permission:admin.access')
 * Must run after the `auth` middleware.
 */
final readonly class EnsurePlatformPermission
{
    public function __construct(private PermissionResolver $permissions) {}

    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        // Account status can change while a session is alive (lock, suspend, disable).
        abort_if($user === null || $user->status !== 'ACTIVE', 403);

        abort_unless(
            $this->permissions->has((int) $user->id, PlatformPermission::from($permission)),
            403,
        );

        return $next($request);
    }
}
