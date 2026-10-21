<?php

declare(strict_types=1);

namespace App\Infrastructure\Content\Jobs;

use App\Application\Content\UseCases\ManagePostInteractions\PublishLikeNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class ProcessLikeNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $likerId,
        public int $postId,
        public int $ownerId,
        public string $blogSlug,
        public string $postSlug,
    ) {}

    public function handle(PublishLikeNotificationService $service): void
    {
        $service->execute($this->likerId, $this->postId, $this->ownerId, $this->blogSlug, $this->postSlug);
    }
}
