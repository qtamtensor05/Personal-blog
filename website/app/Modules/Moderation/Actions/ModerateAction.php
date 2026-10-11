<?php

namespace App\Modules\Moderation\Actions;

use App\Models\User;
use App\Shared\Contracts\AuditRecorder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

final class ModerateAction
{
    public function execute(User $actor, string $kind, int $id, array $input): void
    {
        abort_unless($actor->canAccessApplication(), 403);
        $permission = ['blogs' => 'platform.blogs.moderate', 'reports' => 'platform.content.moderate', 'users' => 'platform.users.restrict'][$kind] ?? null;
        abort_unless($permission && $actor->hasPlatformPermission($permission), 403);
        $states = ['blogs' => 'HIDDEN,SUSPENDED,PUBLISHED', 'reports' => 'REVIEWING,RESOLVED,DISMISSED', 'users' => 'ACTIVE,SUSPENDED,DISABLED'];
        $data = Validator::make($input, ['status' => 'required|in:'.$states[$kind], 'reason' => 'required|string|min:10|max:2000'])->validate();
        DB::transaction(function () use ($actor, $kind, $id, $data): void {
            $table = $kind === 'reports' ? 'content_reports' : $kind;
            $row = DB::table($table)->where('id', $id)->lockForUpdate()->firstOrFail();
            $values = ['status' => $data['status'], 'updated_at' => now()];
            if ($kind === 'blogs' && $data['status'] === 'PUBLISHED') {
                abort_unless(DB::table('blog_designs')->where('blog_id', $id)->whereNotNull('published_data')->exists(), 422, 'Blog chưa có giao diện được xuất bản.');
                $values['published_at'] = $row->published_at ?: now();
            }
            if ($kind === 'reports') {
                $values += ['assigned_to' => $actor->id, 'resolution_note' => $data['reason'], 'resolved_by' => in_array($data['status'], ['RESOLVED', 'DISMISSED']) ? $actor->id : null, 'resolved_at' => in_array($data['status'], ['RESOLVED', 'DISMISSED']) ? now() : null];
            }
            if ($kind === 'users') {
                abort_if($id === (int) $actor->id || DB::table('user_platform_roles')->where('user_id', $id)->exists(), 403, 'Không được thay đổi tài khoản quản trị tại đây.');
                $values['remember_token'] = Str::random(60);
                if ($data['status'] === 'ACTIVE') {
                    $values['status'] = $row->email_verified_at ? 'ACTIVE' : 'PENDING_VERIFICATION';
                    $values['locked_until'] = null;
                    DB::table('account_restrictions')->where('user_id', $id)->where('status', 'ACTIVE')->update(['status' => 'REVOKED', 'revoked_by' => $actor->id, 'revoked_at' => now(), 'revoke_reason' => $data['reason'], 'updated_at' => now()]);
                } else {
                    DB::table('account_restrictions')->insert(['user_id' => $id, 'kind' => $data['status'] === 'SUSPENDED' ? 'SUSPEND' : 'DISABLE', 'reason' => $data['reason'], 'issued_by' => $actor->id, 'starts_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
                }
                DB::table('sessions')->where('user_id', $id)->delete();
            }
            DB::table($table)->where('id', $id)->update($values);
            app(AuditRecorder::class)->record($actor->id, $kind.'.moderated', $kind, $id, $kind === 'blogs' ? $id : ($row->blog_id ?? null), [], ['status' => $values['status'], 'reason' => $data['reason']]);
        });
    }
}
