<?php

namespace App\Modules\Blogs\Actions;

use App\Models\User;
use App\Modules\Blogs\OwnerContext;
use App\Shared\Contracts\AuditRecorder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class SaveBlogSettingsAction
{
    public function execute(User $actor, int $blog, array $input, ?array $original = null): void
    {
        app(OwnerContext::class)->resolve($actor, $blog, true);

        $data = Validator::make($input, [
            'name' => 'required|string|max:160',
            'slug' => 'required|string|max:160',
            'description' => 'nullable|string|max:2000',
            'visibility' => 'required|in:PUBLIC,UNLISTED,PRIVATE',
            'discoverable' => 'boolean',
            'locale' => 'sometimes|required|string|in:vi,en',
            'timezone' => 'sometimes|required|string|in:Asia/Ho_Chi_Minh,UTC,Asia/Bangkok,Asia/Singapore,Asia/Tokyo,Europe/London,America/New_York',
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string|max:500',
            'posts_per_page' => 'required|integer|min:1|max:50',
            'logo_media_id' => 'nullable|integer',
            'favicon_media_id' => 'nullable|integer',
            'og_media_id' => 'nullable|integer',
            // social_links: validated as individual nullable string fields
            'social_links' => 'nullable|array',
            'social_links.twitter' => 'nullable|string|max:255',
            'social_links.facebook' => 'nullable|string|max:255',
            'social_links.instagram' => 'nullable|string|max:255',
            'social_links.youtube' => 'nullable|string|max:255',
            'social_links.github' => 'nullable|string|max:255',
            'social_links.linkedin' => 'nullable|string|max:255',
        ])->validate();

        // Normalize slug
        $normalizedSlug = Str::slug($data['slug']);
        if (Str::length($normalizedSlug) > 160) {
            throw ValidationException::withMessages(['slug' => 'Đường dẫn sau chuẩn hóa không được vượt quá 160 ký tự.']);
        }
        if ($normalizedSlug === '') {
            throw ValidationException::withMessages([
                'slug' => 'Đường dẫn không hợp lệ sau chuẩn hóa. Vui lòng dùng chữ thường, số và dấu gạch ngang.',
            ]);
        }
        $data['slug'] = $normalizedSlug;

        // Omitted keys are unchanged; an explicit empty value removes one link.
        $socialLinks = array_intersect_key($data['social_links'] ?? [], array_flip([
            'twitter', 'facebook', 'instagram', 'youtube', 'github', 'linkedin',
        ]));

        DB::transaction(function () use ($actor, $blog, $data, $socialLinks, $original): void {
            DB::table('blogs')->where('id', $blog)->lockForUpdate()->first();
            app(OwnerContext::class)->resolve($actor, $blog, true);

            if ($original !== null) {
                // Only write edits relative to this form's snapshot. Unchanged fields
                // retain the latest stored values, including concurrent slug changes.
                $data = array_filter($data, fn ($value, $key) => $key !== 'social_links'
                    && ($key === 'discoverable'
                        ? (bool) $value !== (bool) ($original[$key] ?? false)
                        : (string) $value !== (string) ($original[$key] ?? '')), ARRAY_FILTER_USE_BOTH);
            }

            $settings = DB::table('blog_settings')->where('blog_id', $blog)->lockForUpdate()->first();
            $latestLinks = json_decode($settings?->social_links ?? '{}', true) ?? [];
            foreach ($socialLinks as $key => $value) {
                $value = trim($value ?? '');
                if ($original !== null && $value === trim($original['social_links'][$key] ?? '')) {
                    continue;
                }
                if ($value === '') {
                    unset($latestLinks[$key]);
                } else {
                    $latestLinks[$key] = $value;
                }
            }

            // Validate media assets belong to this blog
            foreach (
                collect([
                    $data['logo_media_id'] ?? null,
                    $data['favicon_media_id'] ?? null,
                    $data['og_media_id'] ?? null,
                ])->filter()->unique()->sort() as $id
            ) {
                if (! DB::table('media_assets')
                    ->where(['id' => $id, 'blog_id' => $blog, 'scope' => 'BLOG', 'status' => 'READY'])
                    ->whereNull('deleted_at')
                    ->lockForUpdate()
                    ->first()) {
                    throw ValidationException::withMessages(['media' => 'Ảnh cài đặt không thuộc blog hoặc chưa sẵn sàng.']);
                }
            }

            // Update blogs table: name, slug, description, visibility, discoverable
            $blogsData = array_intersect_key($data, array_flip(['name', 'slug', 'description', 'visibility', 'discoverable']));
            $blogsData['updated_at'] = now();

            try {
                DB::table('blogs')->where('id', $blog)->update($blogsData);
            } catch (QueryException $e) {
                // MySQL error 1062 = unique constraint violation (slug collision)
                if ($e->errorInfo[1] === 1062) {
                    throw ValidationException::withMessages([
                        'slug' => 'Đường dẫn đã được sử dụng bởi blog khác. Hãy chọn đường dẫn khác.',
                    ]);
                }
                throw $e;
            }

            // Update blog_settings table
            $settingsData = array_intersect_key($data, array_flip([
                'locale', 'timezone', 'seo_title', 'seo_description',
                'posts_per_page', 'logo_media_id', 'favicon_media_id', 'og_media_id',
            ]));
            if ($socialLinks !== []) {
                $settingsData['social_links'] = $latestLinks !== [] ? json_encode($latestLinks, JSON_UNESCAPED_UNICODE) : null;
            }
            $settingsData['updated_at'] = now();

            DB::table('blog_settings')->where('blog_id', $blog)->update($settingsData);

            app(AuditRecorder::class)->record($actor->id, 'blog.settings_saved', 'blog', $blog, $blog);
        });
    }
}
