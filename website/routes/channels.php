<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('comments.post.{postId}', function (object $user, int $postId): bool {
    // The payload contains only an ID. The fragment endpoint applies the full
    // post visibility and comment visibility policy before returning any body.
    return $user->status === 'ACTIVE';
});
