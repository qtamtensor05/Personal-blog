<?php

use App\Application\Notifications\DTOs\NotificationPage;
use App\Application\Notifications\DTOs\NotificationRecord;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

uses(TestCase::class);

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
