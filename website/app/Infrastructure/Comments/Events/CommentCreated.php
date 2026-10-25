<?php

declare(strict_types=1);

namespace App\Infrastructure\Comments\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class CommentCreated implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public function __construct(public int $postId, public int $commentId) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('comments.post.'.$this->postId)];
    }

    public function broadcastAs(): string
    {
        return 'comment.created';
    }

    /** @return array{comment_id: int} */
    public function broadcastWith(): array
    {
        return ['comment_id' => $this->commentId];
    }
}
