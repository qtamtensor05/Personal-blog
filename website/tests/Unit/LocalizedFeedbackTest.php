<?php

use App\Application\Notifications\DTOs\NotificationPage;
use App\Application\Notifications\DTOs\NotificationRecord;
use App\Presentation\Formatting\NotificationText;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    // Translation rendering must not depend on built frontend assets or a Vite server.
    $this->withoutVite();
});

it('renders translated constraint messages with numeric bounds', function (string $locale): void {
    app()->setLocale($locale);
    $html = Blade::render('<x-input type="number" name="quantity" label="Quantity" min="2" max="12" required />');
    expect($html)->not->toContain('validation.')
        ->toContain(e(__('validation.min.numeric', ['attribute' => 'Quantity', 'min' => 2])))
        ->toContain(e(__('validation.max.numeric', ['attribute' => 'Quantity', 'max' => 12])));
    $text = Blade::render('<x-input name="title" label="Title" minlength="3" maxlength="20" pattern="[a-z]+" />');
    expect($text)->not->toContain('validation.')
        ->toContain(e(__('validation.regex', ['attribute' => 'Title'])))
        ->toContain(e(__('validation.min.string', ['attribute' => 'Title', 'min' => 3])))
        ->toContain(e(__('validation.max.string', ['attribute' => 'Title', 'max' => 20])));
})->with(['vi', 'en']);

it('renders stored report notifications in the viewer locale including legacy text', function (string $locale, string $target): void {
    app()->setLocale($locale);
    $notification = new NotificationRecord('test-report', 1, 'moderation.report_received', [
        'report_id' => 42, 'target_type' => $target,
        'title' => 'OLD TITLE', 'message' => 'OLD MESSAGE',
    ], null, '2026-11-22 10:00:00');
    $html = view('notifications.index', [
        'notifications' => new NotificationPage([$notification], 1, 1, 10),
        'notificationLocations' => [],
        'notificationUnreadCount' => 1,
    ])->render();
    expect($html)->toContain(__('report_notifications.title'))
        ->toContain(__('report_notifications.message', ['id' => 42, 'target' => __('report_notifications.targets.'.$target)]))
        ->toContain(__('report_notifications.open'))
        ->not->toContain('OLD TITLE', 'OLD MESSAGE', 'report_notifications.');
})->with(['vi', 'en'])->with(['BLOG', 'POST', 'COMMENT']);

it('localizes legacy account and moderation notifications while preserving user text', function (string $type, array $data, string $vi, string $en): void {
    $notification = new NotificationRecord('legacy', 1, $type, $data, null, '2026-11-23');
    foreach (['vi' => $vi, 'en' => $en] as $locale => $expected) {
        app()->setLocale($locale);
        $text = NotificationText::for($notification);
        expect($text['message'])->toBe($expected)->and($text['title'])->not->toContain('system_notifications.');
    }
})->with([
    ['account.restricted', ['kind' => 'LOCK', 'message' => 'A LOCK restriction was applied to your account. Reason: Kiểm thử'], 'Tài khoản của bạn đã bị áp dụng biện pháp khóa. Lý do: Kiểm thử', 'A lock restriction was applied to your account. Reason: Kiểm thử'],
    ['account.restriction_lifted', ['message' => 'Your account restriction was lifted. Reason: Đã kiểm thử'], 'Hạn chế đối với tài khoản của bạn đã được gỡ bỏ. Lý do: Đã kiểm thử', 'Your account restriction was lifted. Reason: Đã kiểm thử'],
    ['moderation.action_taken', ['action' => 'hide_comment', 'message' => 'A moderation action (hide_comment) was applied to blog đầu tiên.'], 'Đã áp dụng biện pháp ẩn bình luận đối với blog blog đầu tiên.', 'A moderation action (hide comment) was applied to blog đầu tiên.'],
    ['moderation.suspension_lifted', ['message' => 'The suspension on Blog. Name was lifted.'], 'Blog Blog. Name đã được gỡ đình chỉ.', 'The suspension on Blog. Name was lifted.'],
    ['moderation.review_started', ['report_id' => 42], 'Báo cáo #42 của bạn đang được xem xét.', 'Your report #42 is now being reviewed.'],
    ['moderation.report_closed', ['report_id' => 42, 'status' => 'RESOLVED'], 'Báo cáo #42 của bạn đã được xử lý.', 'Your report #42 was resolved.'],
    ['role.granted', ['role_id' => 2], 'Tài khoản của bạn đã được cấp vai trò #2.', 'Role #2 was granted to your account.'],
    ['role.revoked', ['role_id' => 2], 'Vai trò #2 đã được thu hồi khỏi tài khoản của bạn.', 'Role #2 was removed from your account.'],
    ['permission.updated', ['permission_code' => 'users.manage'], 'Quyền users.manage của tài khoản đã được cập nhật.', 'Permission users.manage was updated for your account.'],
    ['post.liked', ['liker_name' => 'Owner', 'message' => 'Owner đã thích bài viết "Hành trình".'], 'Owner đã thích bài viết "Hành trình".', 'Owner liked your post "Hành trình".'],
    ['post_comment_created', ['actor_name' => 'Owner', 'message' => 'Owner đã bình luận về bài viết "Hành trình".'], 'Owner đã bình luận về bài viết "Hành trình".', 'Owner commented on your post "Hành trình".'],
    ['comment_replied', ['actor_name' => 'Owner'], 'Owner đã trả lời bình luận của bạn.', 'Owner replied to your comment.'],
]);

it('renders localized restriction text safely with structured reasons', function (): void {
    app()->setLocale('vi');
    $notification = new NotificationRecord('restriction', 1, 'account.restricted', [
        'kind' => 'SUSPEND', 'reason' => '<script>unsafe</script> Reason: giữ nguyên',
        'title' => 'Account restriction applied', 'message' => 'obsolete English text',
    ], null, '2026-11-23');
    $html = view('notifications.index', [
        'notifications' => new NotificationPage([$notification], 1, 1, 10),
        'notificationLocations' => [], 'notificationUnreadCount' => 1,
    ])->render();
    expect($html)->toContain('Tài khoản bị hạn chế', 'tạm đình chỉ', '&lt;script&gt;unsafe&lt;/script&gt; Reason: giữ nguyên')
        ->not->toContain('Account restriction applied', 'obsolete English text', '<script>unsafe</script>');
});
