<?php

declare(strict_types=1);

namespace App\Infrastructure\Content\Persistence;

use App\Application\Content\Contracts\LikeNotificationCooldownRepository;
use Illuminate\Support\Facades\DB;

final class QueryBuilderLikeNotificationCooldownRepository implements LikeNotificationCooldownRepository
{
    public function publishIfDue(int $likerId, int $postId, int $recipientId, callable $publish): bool
    {
        return DB::transaction(function () use ($likerId, $postId, $recipientId, $publish): bool {
            $key = ['liker_user_id' => $likerId, 'post_id' => $postId, 'recipient_user_id' => $recipientId];
            $now = now();
            DB::table('post_like_notification_cooldowns')->insertOrIgnore($key + ['last_notified_at' => $now->copy()->subHour()]);
            $row = DB::table('post_like_notification_cooldowns')->where($key)->lockForUpdate()->first();
            // The one-hour cooldown also makes queued retries and rapid unlike/re-like idempotent.
            if ($row === null || $row->last_notified_at > $now->copy()->subHour()->toDateTimeString()) {
                return false;
            }

            if (! $publish()) {
                return false;
            }
            DB::table('post_like_notification_cooldowns')->where($key)->update(['last_notified_at' => $now]);

            return true;
        });
    }
}
