<?php

declare(strict_types=1);

namespace App\Infrastructure\Comments;

use App\Application\Comments\Contracts\CommentSideEffectDispatcher;
use App\Infrastructure\Comments\Jobs\ProcessCommentSideEffects;

final class QueueCommentSideEffectDispatcher implements CommentSideEffectDispatcher
{
    public function dispatchAfterCommit(int $postId, int $commentId, array $notifications): void
    {
        ProcessCommentSideEffects::dispatch($postId, $commentId, $notifications)->afterCommit();
    }
}
