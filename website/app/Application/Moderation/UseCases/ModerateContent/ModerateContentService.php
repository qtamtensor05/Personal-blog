<?php

declare(strict_types=1);

namespace App\Application\Moderation\UseCases\ModerateContent;

use App\Application\Administration\Contracts\AuditLogger;
use App\Application\Blogs\UseCases\ViewPublicBlog\ViewPublicBlogService;
use App\Application\Content\Contracts\PostRepository;
use App\Application\Moderation\Contracts\ContentReportRepository;
use App\Application\Notifications\Contracts\NotificationPublisher;
use App\Domain\Moderation\Enums\ReportStatus;
use DomainException;

/**
 * Minimal moderation: signed-in visitors report a blog or post; admins review,
 * then resolve (optionally suspending the blog or archiving the post) or dismiss.
 */
final readonly class ModerateContentService
{
    public const ACTIONS = ['none', 'suspend_blog', 'archive_post'];

    public function __construct(
        private ContentReportRepository $reports,
        private ViewPublicBlogService $blogs,
        private PostRepository $posts,
        private AuditLogger $audit,
        private NotificationPublisher $notifications,
    ) {}

    public function report(
        int $reporterId,
        string $blogSlug,
        ?string $postSlug,
        string $reason,
    ): void {
        // Only content the reporter can actually see may be reported.
        $blog = $this->blogs->execute($blogSlug, $reporterId)->blog;

        if ($blog->ownerUserId === $reporterId) {
            throw new DomainException('You cannot report your own blog.');
        }

        $postId = null;

        if ($postSlug !== null) {
            $postId =
                $this->posts->findPublishedBySlug($blog->id, $postSlug)?->id ??
                throw new DomainException('Post not found.');
        }

        if ($this->reports->hasPendingReport($reporterId, $blog->id, $postId)) {
            throw new DomainException('You have already reported this. It is being reviewed.');
        }

        $this->reports->create($blog->id, $postId, $reporterId, $reason);
    }

    public function startReview(int $adminId, int $reportId): void
    {
        $report = $this->pending($reportId);
        $this->reports->startReview($reportId, $adminId);

        if ($report->reporter_user_id !== null) {
            $this->notifications->publish(
                (int) $report->reporter_user_id,
                'moderation.review_started',
                [
                    'title' => 'Report under review',
                    'message' => "Your report #{$reportId} is now being reviewed.",
                    'report_id' => $reportId,
                ],
            );
        }
    }

    public function close(
        int $adminId,
        int $reportId,
        ReportStatus $status,
        string $action,
        ?string $note,
    ): void {
        $report = $this->pending($reportId);

        if (! in_array($status, [ReportStatus::Resolved, ReportStatus::Dismissed], true)) {
            throw new DomainException('A report can only be resolved or dismissed.');
        }

        if ($status === ReportStatus::Dismissed && $action !== 'none') {
            throw new DomainException('A dismissed report cannot take action on content.');
        }

        match ($action) {
            'none' => null,
            'suspend_blog' => $this->reports->suspendBlog((int) $report->blog_id),
            'archive_post' => $report->post_id !== null
                ? $this->reports->archivePost((int) $report->blog_id, (int) $report->post_id)
                : throw new DomainException('This report is not about a post.'),
            default => throw new DomainException('Unknown moderation action.'),
        };

        $this->reports->close($reportId, $status, $adminId, $note);

        $this->audit->log(
            $adminId,
            'content_report.closed',
            'content_report',
            (string) $reportId,
            [
                'status' => $report->status,
                'blog_status' => $report->blog_status,
                'post_status' => $report->post_status,
            ],
            ['status' => $status->value, 'action' => $action, 'note' => $note],
            (int) $report->blog_id,
        );

        if ($report->reporter_user_id !== null) {
            $this->notifications->publish(
                (int) $report->reporter_user_id,
                'moderation.report_closed',
                [
                    'title' => 'Report closed',
                    'message' => "Your report #{$reportId} was {$status->value}.",
                    'report_id' => $reportId,
                    'status' => $status->value,
                ],
            );
        }

        if ($action !== 'none') {
            $this->notifications->publish((int) $report->owner_user_id, 'moderation.action_taken', [
                'title' => 'Moderation action applied',
                'message' => "A moderation action ({$action}) was applied to {$report->blog_name}.",
                'report_id' => $reportId,
                'action' => $action,
            ]);
        }
    }

    public function liftBlogSuspension(int $adminId, int $reportId): void
    {
        $report = $this->reports->find($reportId) ?? throw new DomainException('Report not found.');

        if ($report->blog_status !== 'SUSPENDED') {
            throw new DomainException('This blog is not suspended.');
        }

        $status = $this->reports->liftBlogSuspension((int) $report->blog_id);

        $this->audit->log(
            $adminId,
            'blog.suspension_lifted',
            'blog',
            (string) $report->blog_id,
            ['status' => 'SUSPENDED'],
            ['status' => $status, 'report_id' => $reportId],
            (int) $report->blog_id,
        );

        $this->notifications->publish(
            (int) $report->owner_user_id,
            'moderation.suspension_lifted',
            [
                'title' => 'Blog suspension lifted',
                'message' => "The suspension on {$report->blog_name} was lifted.",
                'report_id' => $reportId,
            ],
        );
    }

    private function pending(int $reportId): object
    {
        $report = $this->reports->find($reportId) ?? throw new DomainException('Report not found.');

        if (! ReportStatus::from($report->status)->isPending()) {
            throw new DomainException('This report is already closed.');
        }

        return $report;
    }
}
