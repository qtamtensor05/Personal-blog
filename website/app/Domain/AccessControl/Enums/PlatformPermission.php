<?php

declare(strict_types=1);

namespace App\Domain\AccessControl\Enums;

/**
 * Platform-wide permissions. The schema only allows scope = 'PLATFORM',
 * so there are no per-blog permissions.
 */
enum PlatformPermission: string
{
    case AccessAdminConsole = 'admin.access';
    case ManageUsers = 'users.manage';
    case ManageRoles = 'roles.manage';
    case ManageTemplates = 'templates.manage';
    case ManageTemplateCategories = 'template_categories.manage';
    case ModerateContent = 'content_reports.manage';
    case ViewAuditLogs = 'audit_logs.view';
    case ManagePlatformSettings = 'platform_settings.manage';

    public function description(): string
    {
        return match ($this) {
            self::AccessAdminConsole => 'Open the Admin Console.',
            self::ManageUsers => 'View users and change account status.',
            self::ManageRoles => 'Assign platform roles to users.',
            self::ManageTemplates => 'Create, edit, preview and publish templates.',
            self::ManageTemplateCategories => 'Manage template categories.',
            self::ModerateContent => 'Review and resolve content reports.',
            self::ViewAuditLogs => 'Read admin and security audit logs.',
            self::ManagePlatformSettings => 'Change platform settings.',
        };
    }
}
