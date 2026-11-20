<?php

declare(strict_types=1);

namespace App\Application\Comments\Contracts;

interface CommentSideEffectDispatcher
{
    /** @param array<int, array{recipient_id: int, type: string, data: array<string, mixed>}> $notifications */
    public function dispatchAfterCommit(int $postId, int $commentId, array $notifications): void;
}
