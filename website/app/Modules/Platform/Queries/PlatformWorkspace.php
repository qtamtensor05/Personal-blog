<?php

namespace App\Modules\Platform\Queries;

use App\Models\User;
use Illuminate\Support\Facades\DB;

final class PlatformWorkspace
{
    public const SECTIONS = ['templates' => ['Mẫu giao diện', 'platform.templates.manage'], 'users' => ['Người dùng', 'platform.users.manage'], 'blogs' => ['Blog', 'platform.blogs.moderate'], 'topics' => ['Chủ đề', 'platform.topics.manage'], 'media' => ['Media', 'platform.media.manage'], 'reports' => ['Báo cáo vi phạm', 'platform.content.moderate'], 'settings' => ['Cài đặt', 'platform.settings.manage'], 'audit' => ['Nhật ký', 'platform.audit.view'], 'notifications' => ['Thông báo', 'platform.dashboard.view']];

    public function authorize(User $actor, string $section): void
    {
        abort_unless(isset(self::SECTIONS[$section]) && $actor->canAccessApplication() && $actor->hasPlatformPermission(self::SECTIONS[$section][1]), 403);
    }

    public function read(User $actor, string $section, string $q = '', string $status = '', int $page = 1, ?int $category = null): array
    {
        $this->authorize($actor, $section);
        $table = ['reports' => 'content_reports', 'topics' => 'blog_topics', 'media' => 'media_assets', 'audit' => 'admin_audit_logs'][$section] ?? $section;
        $stats = [];
        if ($section === 'templates') {
            $stats = ['Tổng số mẫu' => DB::table('templates')->whereNull('deleted_at')->count(), 'Đã xuất bản' => DB::table('templates')->whereNull('deleted_at')->where('status', 'PUBLISHED')->count(), 'Bản nháp' => DB::table('templates')->whereNull('deleted_at')->where(fn ($b) => $b->where('status', 'DRAFT')->orWhereNotNull('draft_data'))->count(), 'Blog đang sử dụng' => DB::table('blog_designs')->whereNotNull('template_id')->whereNotNull('published_data')->count()];
        }
        if ($section === 'settings') {
            return ['rows' => collect(), 'stats' => [], 'settings' => json_decode(DB::table('platform_settings')->where('id', 1)->value('config_json') ?: '{}', true)];
        }
        $query = DB::table($table);
        if ($section === 'templates') {
            $query->select('templates.*')->selectSub(DB::table('media_assets')->select('secure_url')->whereColumn('id', 'templates.preview_media_id')->where(['scope' => 'PLATFORM', 'status' => 'READY', 'delivery_type' => 'upload'])->whereNull('deleted_at')->limit(1), 'preview_url');
        }
        if (in_array($section, ['templates', 'users', 'blogs', 'media'])) {
            $query->whereNull('deleted_at');
        }
        if ($section === 'users') {
            $query->select('id', 'name', 'email', 'status', 'created_at');
        }
        if ($section === 'media') {
            $query->where('scope', 'PLATFORM');
        }
        if ($section === 'notifications') {
            $query->where('recipient_user_id', $actor->id);
        }
        $searchColumn = ['templates' => 'name', 'users' => 'name', 'blogs' => 'name', 'topics' => 'name', 'media' => 'original_name', 'reports' => 'reason', 'audit' => 'action', 'notifications' => 'type'][$section];
        if ($q !== '') {
            $query->where(fn ($search) => $search->where($searchColumn, 'like', '%'.$q.'%')->when($section === 'templates', fn ($s) => $s->orWhere('description', 'like', '%'.$q.'%')));
        }
        if ($section === 'templates' && $category) {
            $query->whereIn('id', DB::table('template_category_assignments')->select('template_id')->where('category_id', $category));
        }
        if ($status !== '' && in_array($section, ['templates', 'users', 'blogs', 'reports', 'media'])) {
            $query->where('status', $status);
        }

        return ['rows' => $query->orderByDesc('created_at')->paginate(12, ['*'], 'page', $page), 'stats' => $stats];
    }

    public function template(User $actor, int $id): object
    {
        $this->authorize($actor, 'templates');

        return DB::table('templates')->where('id', $id)->whereNull('deleted_at')->firstOrFail();
    }

    public function categories()
    {
        return DB::table('template_categories')->where('is_active', true)->get();
    }

    public function templateMedia(User $actor)
    {
        $this->authorize($actor, 'templates');

        return DB::table('media_assets')->where(['scope' => 'PLATFORM', 'status' => 'READY', 'delivery_type' => 'upload'])->whereNull('deleted_at')->get(['id', 'original_name']);
    }

    public function templateCategory(User $actor, int $id): ?int
    {
        $this->authorize($actor, 'templates');

        return DB::table('template_category_assignments')->where('template_id', $id)->value('category_id');
    }

    public function overview(User $actor): array
    {
        abort_unless($actor->canAccessApplication() && $actor->hasPlatformPermission('platform.dashboard.view'), 403);

        return ['Người dùng' => DB::table('users')->whereNull('deleted_at')->count(), 'Blog' => DB::table('blogs')->whereNull('deleted_at')->count(), 'Mẫu giao diện' => DB::table('templates')->whereNull('deleted_at')->count(), 'Báo cáo đang mở' => DB::table('content_reports')->whereIn('status', ['OPEN', 'REVIEWING'])->count()];
    }
}
