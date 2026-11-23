<?php

declare(strict_types=1);

namespace App\Presentation\Formatting;

use App\Application\Notifications\DTOs\NotificationRecord;

final class NotificationText
{
    /** @return array{title: string, message: string} */
    public static function for(NotificationRecord $notification): array
    {
        $data = $notification->data;
        $legacy = (string) ($data['message'] ?? '');
        $capture = static function (string $pattern) use ($legacy): string {
            return preg_match($pattern, $legacy, $matches) === 1 ? $matches[1] : '';
        };
        $key = str_replace('.', '_', $notification->type);
        if (! in_array($key, ['account_restricted', 'account_restriction_lifted', 'role_granted', 'role_revoked', 'permission_updated', 'moderation_review_started', 'moderation_report_closed', 'moderation_action_taken', 'moderation_suspension_lifted', 'post_liked', 'post_comment_created', 'comment_replied'], true)) {
            return ['title' => (string) ($data['title'] ?? __('notifications.fallback_title')), 'message' => $legacy];
        }
        $kind = (string) ($data['kind'] ?? $capture('/^A (\w+) restriction was applied to your account\./s'));
        $action = (string) ($data['action'] ?? $capture('/^A moderation action \(([^)]+)\) was applied to /s'));
        $status = (string) ($data['status'] ?? $capture('/^Your report #\d+ was (\w+)\.$/s'));
        $label = static function (string $group, string $value): string {
            $translation = 'system_notifications.'.$group.'.'.$value;

            return __($translation) === $translation ? $value : __($translation);
        };
        $parameters = [
            'id' => $data['report_id'] ?? '',
            'role' => $data['role_id'] ?? '',
            'permission' => $data['permission_code'] ?? '',
            'kind' => $label('kinds', $kind),
            'action' => $label('actions', $action),
            'status' => $label('statuses', $status),
            'actor' => $data['liker_name'] ?? $data['actor_name'] ?? '',
            'post' => $data['post_title'] ?? $capture('/^.*(?: đã thích bài viết | đã bình luận về bài viết )"(.*)"\.$/s'),
            'blog' => $data['blog_name'] ?? ($notification->type === 'moderation.suspension_lifted'
                ? $capture('/^The suspension on (.*) was lifted\.$/s')
                : $capture('/^A moderation action \([^)]+\) was applied to (.*)\.$/s')),
        ];
        $message = __('system_notifications.'.$key.'.message', $parameters);
        if (in_array($notification->type, ['account.restricted', 'account.restriction_lifted'], true)) {
            $reason = $data['reason'] ?? $capture('/^.*?\. Reason: (.*)$/s');
            if ($reason !== '') {
                $message .= ' '.__('system_notifications.reason', ['reason' => $reason]);
            }
        }

        return ['title' => __('system_notifications.'.$key.'.title'), 'message' => $message];
    }
}
