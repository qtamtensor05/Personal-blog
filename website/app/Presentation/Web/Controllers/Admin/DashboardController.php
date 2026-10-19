<?php

declare(strict_types=1);

namespace App\Presentation\Web\Controllers\Admin;

use App\Application\AccessControl\Services\PermissionResolver;
use App\Domain\AccessControl\Enums\PlatformPermission;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class DashboardController extends Controller
{
    public function __invoke(Request $request, PermissionResolver $permissions): View
    {
        $userId = (int) $request->user()->id;

        return view('admin.dashboard', [
            'canManageUsers' => $permissions->has($userId, PlatformPermission::ManageUsers),
            'canModerateContent' => $permissions->has($userId, PlatformPermission::ModerateContent),
            'canManageTemplates' => $permissions->has($userId, PlatformPermission::ManageTemplates),
            'canManageSettings' => $permissions->has(
                $userId,
                PlatformPermission::ManagePlatformSettings,
            ),
            'canViewAuditLogs' => $permissions->has($userId, PlatformPermission::ViewAuditLogs),
        ]);
    }
}
