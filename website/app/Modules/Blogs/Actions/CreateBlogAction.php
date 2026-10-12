<?php

namespace App\Modules\Blogs\Actions;

use App\Models\User;
use App\Modules\Design\DesignDocument;
use Illuminate\Database\QueryException;
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

        try {
            return DB::transaction(function () use ($actor, $input): int {
                // Lock the user row to serialize quota checks for the same owner.
                DB::table('users')->where('id', $actor->id)->lockForUpdate()->first();

                $template = isset($input['template_id'])
                    ? DB::table('templates')->where('id', $input['template_id'])->where('status', 'PUBLISHED')->whereNull('deleted_at')->sharedLock()->first()
                    : null;
                if (isset($input['template_id']) && ! $template) {
                    throw ValidationException::withMessages(['template_id' => 'Mẫu không còn cho phép chọn. Hãy chọn lại.']);
                }
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

                $rawSlug = isset($input['slug']) && trim((string) $input['slug']) !== ''
                    ? (string) $input['slug']
                    : (string) ($input['name'] ?? '');
                $slug = Str::slug($rawSlug);
                if (Str::length($slug) > 160) {
                    throw ValidationException::withMessages(['slug' => 'Đường dẫn sau chuẩn hóa không được vượt quá 160 ký tự.']);
                }
                if ($slug === '') {
                    throw ValidationException::withMessages(['slug' => 'Không thể sinh đường dẫn hợp lệ từ tên đã nhập. Hãy nhập đường dẫn thủ công.']);
                }

                // Pre-check for duplicate before insert (best-effort; race handled by DB constraint below)
                if (DB::table('blogs')->where('slug', $slug)->exists()) {
                    throw ValidationException::withMessages(['slug' => 'Đường dẫn đã được sử dụng. Hãy chọn đường dẫn khác.']);
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
                    'storage_quota_bytes' => (int) data_get($config, 'limits.defaultStorageQuotaBytes', 104857600),
                    'post_limit' => data_get($config, 'limits.postsPerBlog'),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                if ($template) {
                    DB::table('blog_designs')->insert([
                        'blog_id' => $blogId,
                        'draft_template_id' => $template->id,
                        'draft_data' => json_encode(DesignDocument::snapshot($template), JSON_THROW_ON_ERROR),
                        'updated_by' => $actor->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                return $blogId;
            });
        } catch (ValidationException $e) {
            throw $e;
        } catch (QueryException $e) {
            // MySQL error 1062 = unique constraint violation; concurrent slug race
            if ($e->errorInfo[1] === 1062) {
                throw ValidationException::withMessages(['slug' => 'Đường dẫn đã được sử dụng bởi request đồng thời. Hãy thử lại với đường dẫn khác.']);
            }
            throw $e;
        }
    }
}
