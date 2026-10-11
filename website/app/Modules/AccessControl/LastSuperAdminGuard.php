<?php

namespace App\Modules\AccessControl;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class LastSuperAdminGuard
{
    public function assertCanDeactivate(int $userId): void
    {
        $superId = DB::table('platform_roles')->where('code', 'SUPER_ADMIN')->value('id');
        $grants = DB::table('user_platform_roles')->where('role_id', $superId)->where('user_id', $userId)->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));
        if (! $superId || ! $grants->exists()) {
            return;
        }
        $other = DB::table('user_platform_roles as g')->join('users as u', 'u.id', '=', 'g.user_id')->where('g.role_id', $superId)->where('g.user_id', '!=', $userId)->where(fn ($q) => $q->whereNull('g.expires_at')->orWhere('g.expires_at', '>', now()))->where('u.status', 'ACTIVE')->whereNotNull('u.email_verified_at')->whereNull('u.deleted_at')->where(fn ($q) => $q->whereNull('u.locked_until')->orWhere('u.locked_until', '<=', now()))->exists();
        if (! $other) {
            throw ValidationException::withMessages(['access' => 'Không được vô hiệu hóa Super Admin khả dụng cuối cùng. Hãy có Super Admin thứ hai trước khi đổi email hoặc thu hồi quyền.']);
        }
    }
}
