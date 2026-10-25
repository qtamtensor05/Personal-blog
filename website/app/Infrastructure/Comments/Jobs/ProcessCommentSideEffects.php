<?php

declare(strict_types=1);

namespace App\Infrastructure\Comments\Jobs;

use App\Application\Notifications\Contracts\NotificationPublisher;
use App\Infrastructure\Comments\Events\CommentCreated;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class ProcessCommentSideEffects implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @param array<int, array{recipient_id: int, type: string, data: array<string, mixed>}> $notifications */
    public function __construct(public int $postId, public int $commentId, public array $notifications) {}

    public function handle(NotificationPublisher $publisher): void
    {
        foreach ($this->notifications as $notification) {
            $publisher->publish($notification['recipient_id'], $notification['type'], $notification['data']);
        }

        event(new CommentCreated($this->postId, $this->commentId));
    }
}
