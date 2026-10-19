<?php

declare(strict_types=1);

namespace App\Infrastructure\Notifications;

use App\Application\Notifications\Contracts\NotificationRepository;
use App\Application\Notifications\DTOs\NotificationPage;
use App\Application\Notifications\DTOs\NotificationRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class QueryBuilderNotificationRepository implements NotificationRepository
{
    public function create(int $recipientUserId, string $type, array $data): string
    {
        $id = (string) Str::uuid();
        $now = now();

        DB::table('notifications')->insert([
            'id' => $id,
            'recipient_user_id' => $recipientUserId,
            'type' => $type,
            'data' => json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            'read_at' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $id;
    }

    public function paginateForRecipient(int $recipientUserId, int $page, int $perPage): NotificationPage
    {
        $query = DB::table('notifications')->where('recipient_user_id', $recipientUserId);
        $total = (clone $query)->count();
        $items = $query->orderByDesc('created_at')->orderByDesc('id')
            ->offset(($page - 1) * $perPage)->limit($perPage)->get()
            ->map(fn (object $row): NotificationRecord => $this->map($row))->all();

        return new NotificationPage($items, $total, $page, $perPage);
    }

    public function unreadCount(int $recipientUserId): int
    {
        return DB::table('notifications')->where('recipient_user_id', $recipientUserId)->whereNull('read_at')->count();
    }

    public function markReadForRecipient(string $id, int $recipientUserId): bool
    {
        $notification = DB::table('notifications')->where('id', $id)
            ->where('recipient_user_id', $recipientUserId)->first(['read_at']);

        if ($notification === null) {
            return false;
        }

        if ($notification->read_at === null) {
            DB::table('notifications')->where('id', $id)->where('recipient_user_id', $recipientUserId)
                ->update(['read_at' => now(), 'updated_at' => now()]);
        }

        return true;
    }

    public function markAllReadForRecipient(int $recipientUserId): int
    {
        $now = now();

        return DB::table('notifications')->where('recipient_user_id', $recipientUserId)->whereNull('read_at')
            ->update(['read_at' => $now, 'updated_at' => $now]);
    }

    public function deleteForRecipient(string $id, int $recipientUserId): bool
    {
        return DB::table('notifications')
            ->where('id', $id)
            ->where('recipient_user_id', $recipientUserId)
            ->delete() > 0;
    }

    private function map(object $row): NotificationRecord
    {
        $data = json_decode((string) $row->data, true, flags: JSON_THROW_ON_ERROR);

        return new NotificationRecord(
            (string) $row->id,
            (int) $row->recipient_user_id,
            (string) $row->type,
            is_array($data) ? $data : [],
            $row->read_at !== null ? (string) $row->read_at : null,
            (string) $row->created_at,
        );
    }
}
