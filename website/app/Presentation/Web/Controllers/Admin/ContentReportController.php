<?php

declare(strict_types=1);

namespace App\Presentation\Web\Controllers\Admin;

use App\Application\Moderation\Contracts\ContentReportRepository;
use App\Application\Moderation\UseCases\ModerateContent\ModerateContentService;
use App\Domain\Moderation\Enums\ReportStatus;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class ContentReportController extends Controller
{
    private const PER_PAGE = 25;

    public function index(Request $request, ContentReportRepository $reports): View
    {
        $data = $request->validate([
            'status' => ['nullable', 'in:OPEN,REVIEWING,RESOLVED,DISMISSED'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $page = (int) ($data['page'] ?? 1);
        $result = $reports->search(
            isset($data['status']) ? ReportStatus::from($data['status']) : null,
            $page,
            self::PER_PAGE,
        );

        return view('admin.reports.index', [
            'reports' => $result['items'],
            'page' => $page,
            'lastPage' => max(1, (int) ceil($result['total'] / self::PER_PAGE)),
            'filters' => $data,
        ]);
    }

    public function show(int $report, ContentReportRepository $reports): View
    {
        return view('admin.reports.show', [
            'report' => $reports->find($report) ?? abort(404),
            'actions' => ModerateContentService::ACTIONS,
        ]);
    }

    public function review(
        int $report,
        Request $request,
        ModerateContentService $service,
    ): RedirectResponse {
        return $this->run(
            $report,
            fn () => $service->startReview((int) $request->user()->id, $report),
            __('admin.report_assigned'),
        );
    }

    public function close(
        int $report,
        Request $request,
        ModerateContentService $service,
    ): RedirectResponse {
        $data = $request->validate([
            'status' => ['required', 'in:RESOLVED,DISMISSED'],
            'action' => ['required', 'in:'.implode(',', ModerateContentService::ACTIONS)],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        return $this->run(
            $report,
            fn () => $service->close(
                (int) $request->user()->id,
                $report,
                ReportStatus::from($data['status']),
                $data['action'],
                $data['note'] ?? null,
            ),
            __('admin.report_closed'),
        );
    }

    public function liftSuspension(
        int $report,
        Request $request,
        ModerateContentService $service,
    ): RedirectResponse {
        return $this->run(
            $report,
            fn () => $service->liftBlogSuspension((int) $request->user()->id, $report),
            __('admin.blog_suspension_lifted'),
        );
    }

    private function run(int $report, callable $action, string $message): RedirectResponse
    {
        try {
            $action();
        } catch (DomainException $exception) {
            return back()
                ->withErrors(['general' => $exception->getMessage()])
                ->withInput();
        }

        return redirect()->route('admin.reports.show', $report)->with('status', $message);
    }
}
