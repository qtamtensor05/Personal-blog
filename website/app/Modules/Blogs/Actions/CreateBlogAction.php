<?php

namespace App\Modules\Blogs\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class CreateBlogAction
{
    public function execute(User $actor, array $input): int
    {
        if (! $actor->canAccessApplication()) {
            throw ValidationException::withMessages([
                'account' => 'Tài khoản phải được xác minh và đang hoạt động.',
            ]);
        }

        return DB::transaction(function () use ($actor, $input): int {
            DB::table('users')->where('id', $actor->id)->lockForUpdate()->first();
            $defaults = DB::table('platform_settings')->where('id', 1)->value('config_json');
            $config = is_string($defaults)
                ? json_decode($defaults, true, 512, JSON_THROW_ON_ERROR)
                : $defaults ?? [];
            $limit = (int) data_get($config, 'limits.maxBlogsPerUser', 3);
            $count = DB::table('blogs')
                ->where('owner_user_id', $actor->id)
                ->whereNull('deleted_at')
                ->lockForUpdate()
                ->count();
            if ($count >= $limit) {
                throw ValidationException::withMessages([
                    'name' => "Bạn đã đạt giới hạn {$limit} blog.",
                ]);
            }

            $slug = Str::slug($input['slug'] ?? $input['name']);
            if ($slug === '' || DB::table('blogs')->where('slug', $slug)->exists()) {
                throw ValidationException::withMessages(['slug' => 'Đường dẫn rỗng hoặc đã được sử dụng.']);
            }
            $blogId = DB::table('blogs')->insertGetId([
                'owner_user_id' => $actor->id,
                'name' => $input['name'],
                'slug' => $slug,
                'description' => $input['description'] ?? null,
                'status' => 'DRAFT',
                'visibility' => 'PUBLIC',
                'discoverable' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('blog_settings')->insert([
                'blog_id' => $blogId,
                'locale' => 'vi',
                'timezone' => 'Asia/Ho_Chi_Minh',
                'posts_per_page' => 10,
                'storage_quota_bytes' => (int) data_get(
                    $config,
                    'limits.defaultStorageQuotaBytes',
                    104857600,
                ),
                'post_limit' => data_get($config, 'limits.postsPerBlog'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $blogId;
        });
    }
}
