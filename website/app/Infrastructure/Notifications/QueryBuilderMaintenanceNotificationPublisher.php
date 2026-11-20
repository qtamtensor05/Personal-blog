<?php

namespace App\Infrastructure\Notifications;

use App\Application\Notifications\Contracts\MaintenanceNotificationPublisher;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

final class QueryBuilderMaintenanceNotificationPublisher implements MaintenanceNotificationPublisher
{
    public function publish(string $message, int $revision): void
    {
        DB::transaction(function () use ($message, $revision): void {
            DB::table('users')->whereNull('deleted_at')->select('id')->chunkById(500, function ($users) use ($message, $revision): void {
                $now = now();
                $rows = [];
                foreach ($users as $user) {
                    // Stable IDs make retrying the same broadcast safe.
                    $rows[] = [
                        'id' => (string) Uuid::uuid5(Uuid::NAMESPACE_URL, 'maintenance:'.$revision.':'.$user->id),
                        'recipient_user_id' => $user->id,
                        'type' => 'platform.maintenance',
                        'data' => json_encode(['message' => $message], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                        'read_at' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
                if ($rows !== []) {
                    DB::table('notifications')->insertOrIgnore($rows);
                }
            });
        });
    }
}
