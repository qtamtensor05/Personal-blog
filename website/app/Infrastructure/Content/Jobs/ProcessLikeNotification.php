<?php

declare(strict_types=1);

namespace App\Infrastructure\Content\Jobs;

use App\Application\Content\UseCases\ManagePostInteractions\PublishLikeNotificationUseCase;
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
        // Kept optional so jobs already queued by older code can still be unserialized.
        public ?string $blogSlug = null,
        public ?string $postSlug = null,
    ) {}

    public function handle(PublishLikeNotificationUseCase $service): void
    {
        $service->execute($this->likerId, $this->postId, $this->ownerId);
    }
}
