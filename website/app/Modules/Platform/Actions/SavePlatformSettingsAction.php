<?php

namespace App\Modules\Platform\Actions;

use App\Models\User;
use App\Shared\Contracts\AuditRecorder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

final class SavePlatformSettingsAction
{
    public const RULES = [
        'branding' => ['name' => 'required|string|max:160'],
        'seo' => ['title' => 'required|string|max:255', 'description' => 'nullable|string|max:500'],
        'discovery' => ['enabled' => 'required|boolean'],
        'limits' => ['maxBlogsPerUser' => 'required|integer|min:1|max:100', 'defaultStorageQuotaBytes' => 'required|integer|min:1048576|max:10737418240', 'maxImageUploadBytes' => 'required|integer|min:1024|max:10485760'],
        'features' => ['registrationEnabled' => 'required|boolean'],
        'security' => ['requireAdmin2fa' => 'required|accepted', 'requireVerifiedEmailForBlog' => 'required|accepted'],
    ];

    public function execute(User $actor, string $group, array $input): void
    {
        abort_unless($actor->canAccessApplication() && $actor->hasPlatformPermission('platform.settings.manage'), 403);
        abort_unless(isset(self::RULES[$group]), 422);
        if (in_array($group, ['security', 'limits'])) {
            abort_unless($actor->hasPlatformPermission('platform.security.manage'), 403);
        }
        $data = Validator::make($input, self::RULES[$group])->validate();
        DB::transaction(function () use ($actor, $group, $data): void {
            $row = DB::table('platform_settings')->where('id', 1)->lockForUpdate()->first();
            $json = json_decode($row->config_json, true);
            $json[$group] = array_replace($json[$group] ?? [], $data);
            DB::table('platform_settings')->where('id', 1)->update(['config_json' => json_encode($json, JSON_THROW_ON_ERROR), 'updated_by' => $actor->id, 'updated_at' => now()]);
            app(AuditRecorder::class)->record($actor->id, 'settings.'.$group, 'platform_settings', 1);
        });
    }
}
