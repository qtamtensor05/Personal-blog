<?php

declare(strict_types=1);

namespace App\Infrastructure\Moderation;

use App\Application\AccessControl\Contracts\PermissionRepository;
use App\Application\Moderation\Contracts\ReportNotificationRecipients;
use App\Domain\AccessControl\Enums\PlatformPermission;
use Illuminate\Support\Facades\DB;

final readonly class QueryBuilderReportNotificationRecipients implements ReportNotificationRecipients
{
    public function __construct(private PermissionRepository $permissions) {}

    public function userIds(): array
    {
        $recipients = [];
        foreach (DB::table('users')->where('status', 'ACTIVE')->whereNull('deleted_at')->select('id')->orderBy('id')->lazyById() as $user) {
            $codes = $this->permissions->codesForUser((int) $user->id);
            if (in_array(PlatformPermission::AccessAdminConsole->value, $codes, true)
                && in_array(PlatformPermission::ModerateContent->value, $codes, true)) {
                $recipients[] = (int) $user->id;
            }
        }

        return $recipients;
    }
}
