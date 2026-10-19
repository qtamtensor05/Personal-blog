<?php

declare(strict_types=1);

namespace App\Infrastructure\Moderation;

use App\Application\Moderation\Contracts\ContentReportRepository;
use App\Domain\Moderation\Enums\ReportStatus;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

final class QueryBuilderContentReportRepository implements ContentReportRepository
{
    private const PENDING = ['OPEN', 'REVIEWING'];

    public function create(int $blogId, ?int $postId, int $reporterId, string $reason): int
    {
        $now = now();

        return (int) DB::table('content_reports')->insertGetId([
            'blog_id' => $blogId,
            'target_type' => $postId === null ? 'BLOG' : 'POST',
            'post_id' => $postId,
            'reporter_user_id' => $reporterId,
            'reason' => $reason,
            'status' => ReportStatus::Open->value,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function hasPendingReport(int $reporterId, int $blogId, ?int $postId): bool
    {
        return DB::table('content_reports')
            ->where('reporter_user_id', $reporterId)
            ->where('blog_id', $blogId)
            ->when(
                $postId === null,
                fn ($q) => $q->whereNull('post_id'),
                fn ($q) => $q->where('post_id', $postId),
            )
            ->whereIn('status', self::PENDING)
            ->exists();
    }

    public function search(?ReportStatus $status, int $page, int $perPage): array
    {
        $builder = $this->joined()->when(
            $status !== null,
            fn ($q) => $q->where('r.status', $status->value),
        );
        $total = (clone $builder)->count();

        $items = $builder
            ->orderByDesc('r.id')
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get()
            ->all();

        return ['items' => $items, 'total' => $total];
    }

    public function find(int $id): ?object
    {
        return $this->joined()->where('r.id', $id)->first();
    }

    public function startReview(int $id, int $adminId): void
    {
        DB::table('content_reports')
            ->where('id', $id)
            ->where('status', ReportStatus::Open->value)
            ->update([
                'status' => ReportStatus::Reviewing->value,
                'assigned_to' => $adminId,
                'updated_at' => now(),
            ]);
    }

    public function close(int $id, ReportStatus $status, int $adminId, ?string $note): void
    {
        $now = now();

        DB::table('content_reports')
            ->where('id', $id)
            ->update([
                'status' => $status->value,
                'resolved_by' => $adminId,
                'resolution_note' => $note,
                'resolved_at' => $now,
                'updated_at' => $now,
            ]);
    }

    public function suspendBlog(int $blogId): void
    {
        DB::table('blogs')
            ->where('id', $blogId)
            ->update(['status' => 'SUSPENDED', 'updated_at' => now()]);
    }

    public function liftBlogSuspension(int $blogId): string
    {
        $publishedAt = DB::table('blogs')->where('id', $blogId)->value('published_at');
        $status = $publishedAt !== null ? 'PUBLISHED' : 'DRAFT';

        DB::table('blogs')
            ->where('id', $blogId)
            ->where('status', 'SUSPENDED')
            ->update(['status' => $status, 'updated_at' => now()]);

        return $status;
    }

    public function archivePost(int $blogId, int $postId): void
    {
        DB::table('posts')
            ->where('blog_id', $blogId)
            ->where('id', $postId)
            ->update(['status' => 'ARCHIVED', 'updated_at' => now()]);
    }

    private function joined(): Builder
    {
        return DB::table('content_reports as r')
            ->join('blogs as b', 'b.id', '=', 'r.blog_id')
            ->leftJoin('posts as p', 'p.id', '=', 'r.post_id')
            ->leftJoin('users as u', 'u.id', '=', 'r.reporter_user_id')
            ->select([
                'r.*',
                'b.name as blog_name',
                'b.slug as blog_slug',
                'b.status as blog_status',
                'b.owner_user_id',
                'p.title as post_title',
                'p.slug as post_slug',
                'p.status as post_status',
                'u.email as reporter_email',
            ]);
    }
}
