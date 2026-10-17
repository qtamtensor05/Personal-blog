<?php

declare(strict_types=1);

namespace App\Presentation\Web\Controllers;

use App\Application\Notifications\Contracts\NotificationRepository;
use App\Application\Notifications\UseCases\ListNotificationsService;
use App\Application\Notifications\UseCases\MarkNotificationReadService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class NotificationController extends Controller
{
    public function index(
        Request $request,
        ListNotificationsService $service,
        NotificationRepository $repository,
    ): View {
        $data = $request->validate(['page' => ['nullable', 'integer', 'min:1']]);
        $userId = (int) $request->user()->id;
        $notifications = $service->execute($userId, (int) ($data['page'] ?? 1));
        $notificationUnreadCount = $repository->unreadCount($userId);

        return view('notifications.index', compact('notifications', 'notificationUnreadCount'));
    }

    public function read(string $notification, Request $request, MarkNotificationReadService $service): RedirectResponse
    {
        try {
            $service->one((int) $request->user()->id, $notification);
        } catch (DomainException) {
            abort(404);
        }

        return back();
    }

    public function readAll(Request $request, MarkNotificationReadService $service): RedirectResponse
    {
        $service->all((int) $request->user()->id);

        return back()->with('status', 'All notifications marked as read.');
    }
}
