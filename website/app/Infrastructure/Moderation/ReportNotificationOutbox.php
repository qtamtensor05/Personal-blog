<?php

declare(strict_types=1);

namespace App\Infrastructure\Moderation;

use App\Application\Moderation\Contracts\ReportNotificationRecipients;
use App\Application\Notifications\Contracts\NotificationPublisher;
use Illuminate\Support\Facades\DB;

final readonly class ReportNotificationOutbox
{
    public function __construct(private ReportNotificationRecipients $recipients, private NotificationPublisher $notifications) {}

    public function deliver(int $reportId): void
    {
        DB::transaction(function () use ($reportId): void {
            $event = DB::table('report_notification_outbox')->where('report_id', $reportId)->lockForUpdate()->first();
            if ($event === null || $event->delivered_at !== null) {
                return;
            }
            foreach ($this->recipients->userIds() as $recipientId) {
                $this->notifications->publish($recipientId, 'moderation.report_received', [
                    'report_id' => $reportId,
                    'target_type' => $event->target_type,
                ]);
            }
            DB::table('report_notification_outbox')->where('report_id', $reportId)->update(['delivered_at' => now(), 'updated_at' => now()]);
        }, 3);
    }

    public function drain(): void
    {
        DB::table('report_notification_outbox')->whereNull('delivered_at')->select('report_id')->chunkById(100, function ($events): void {
            foreach ($events as $event) {
                $this->attempt((int) $event->report_id);
            }
        }, 'report_id');
    }

    public function attempt(int $reportId): void
    {
        try {
            $this->deliver($reportId);
        } catch (\Throwable $exception) {
            report($exception);
        }
    }
}
