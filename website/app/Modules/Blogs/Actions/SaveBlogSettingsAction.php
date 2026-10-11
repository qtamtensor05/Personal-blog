<?php

namespace App\Modules\Blogs\Actions;

use App\Models\User;
use App\Modules\Blogs\OwnerContext;
use App\Shared\Contracts\AuditRecorder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

final class SaveBlogSettingsAction
{
    public function execute(User $actor, int $blog, array $input): void
    {
        app(OwnerContext::class)->resolve($actor, $blog, true);
        $data = Validator::make($input, ['name' => 'required|string|max:160', 'description' => 'nullable|string|max:2000', 'visibility' => 'required|in:PUBLIC,UNLISTED,PRIVATE', 'seo_title' => 'nullable|string|max:255', 'seo_description' => 'nullable|string|max:500', 'posts_per_page' => 'required|integer|min:1|max:50', 'logo_media_id' => 'nullable|integer', 'favicon_media_id' => 'nullable|integer', 'og_media_id' => 'nullable|integer'])->validate();
        DB::transaction(function () use ($actor, $blog, $data): void {
            DB::table('blogs')->where('id', $blog)->lockForUpdate()->first();
            app(OwnerContext::class)->resolve($actor, $blog, true);
            foreach (collect([$data['logo_media_id'] ?? null, $data['favicon_media_id'] ?? null, $data['og_media_id'] ?? null])->filter()->unique()->sort() as $id) {
                if (! DB::table('media_assets')->where(['id' => $id, 'blog_id' => $blog, 'scope' => 'BLOG', 'status' => 'READY'])->whereNull('deleted_at')->lockForUpdate()->first()) {
                    throw ValidationException::withMessages(['media' => 'Ảnh cài đặt không thuộc blog hoặc chưa sẵn sàng.']);
                }
            }
            DB::table('blogs')->where('id', $blog)->update(array_intersect_key($data, array_flip(['name', 'description', 'visibility'])) + ['updated_at' => now()]);
            DB::table('blog_settings')->where('blog_id', $blog)->update(array_intersect_key($data, array_flip(['seo_title', 'seo_description', 'posts_per_page', 'logo_media_id', 'favicon_media_id', 'og_media_id'])) + ['updated_at' => now()]);
            app(AuditRecorder::class)->record($actor->id, 'blog.settings_saved', 'blog', $blog, $blog);
        });
    }
}
