<?php

declare(strict_types=1);

namespace App\Application\Moderation\Contracts;

use App\Domain\Moderation\Enums\ReportStatus;

interface ContentReportRepository
{
    /**
     * $postId is null for BLOG reports and set for POST reports (ck_content_reports_0).
     */
    public function create(int $blogId, ?int $postId, int $reporterId, string $reason): int;

    public function createComment(int $blogId, int $postId, int $commentId, int $reporterId, string $reason): int;

    /**
     * An OPEN or REVIEWING report from this user on the same target already exists.
     */
    public function hasPendingReport(int $reporterId, int $blogId, ?int $postId): bool;

    public function hasPendingCommentReport(int $reporterId, int $commentId): bool;

    /**
     * Rows joined with blog, post and reporter, newest first.
     *
     * @return array{items: list<object>, total: int}
     */
    public function search(?ReportStatus $status, int $page, int $perPage): array;

    public function find(int $id): ?object;

    public function startReview(int $id, int $adminId): void;

    public function close(int $id, ReportStatus $status, int $adminId, ?string $note): void;

    public function suspendBlog(int $blogId): void;

    /**
     * Back to PUBLISHED if the blog was ever published, otherwise DRAFT.
     */
    public function liftBlogSuspension(int $blogId): string;

    public function archivePost(int $blogId, int $postId): void;

    public function hideComment(int $commentId): void;
}
