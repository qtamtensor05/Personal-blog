<?php

declare(strict_types=1);

namespace App\Presentation\Web\Controllers;

use App\Application\Blogs\Exceptions\BlogNotFound;
use App\Application\Moderation\UseCases\ModerateContent\ModerateContentService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

/**
 * Signed-in visitors report a blog or a post.
 */
final class ContentReportController extends Controller
{
    public function __invoke(
        string $slug,
        Request $request,
        ModerateContentService $service,
    ): RedirectResponse {
        $data = $request->validate([
            'post' => ['nullable', 'string', 'max:160', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'reason' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        try {
            $service->report(
                (int) $request->user()->id,
                $slug,
                $data['post'] ?? null,
                $data['reason'],
            );
        } catch (BlogNotFound) {
            abort(404);
        } catch (DomainException $exception) {
            return back()->withErrors(['report' => $exception->getMessage()]);
        }

        return back()->with(
            'report_status',
            'Thanks. Your report has been sent to the moderators.',
        );
    }
}
