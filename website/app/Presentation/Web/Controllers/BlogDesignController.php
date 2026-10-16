<?php

declare(strict_types=1);

namespace App\Presentation\Web\Controllers;

use App\Application\BlogDesign\Contracts\BlogDesignRepository;
use App\Application\BlogDesign\Services\OwnedBlogGuard;
use App\Application\BlogDesign\UseCases\ApplyTemplate\ApplyTemplateService;
use App\Application\BlogDesign\UseCases\PublishDesign\PublishDesignService;
use App\Application\BlogDesign\UseCases\SaveDesignDraft\SaveDesignDraftService;
use App\Application\Blogs\Exceptions\BlogNotFound;
use App\Application\Templates\Contracts\TemplateRepository;
use App\Application\Templates\Exceptions\TemplateNotFound;
use App\Domain\Templates\ValueObjects\TemplateDesignTokens;
use App\Presentation\Web\Requests\SaveDesignDraftRequest;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class BlogDesignController extends Controller
{
    public function edit(
        int $blog,
        Request $request,
        OwnedBlogGuard $guard,
        BlogDesignRepository $designs,
        TemplateRepository $templates,
    ): View {
        try {
            $record = $guard->ensure((int) $request->user()->id, $blog);
        } catch (BlogNotFound) {
            abort(404);
        }

        return view('blogs.design', [
            'blog' => $record,
            'design' => $designs->find($blog),
            'templates' => $templates->published(),
        ]);
    }

    public function applyTemplate(int $blog, Request $request, ApplyTemplateService $apply): RedirectResponse
    {
        $request->validate(['template_id' => ['required', 'integer', 'min:1']]);

        try {
            $apply->execute((int) $request->user()->id, $blog, (int) $request->input('template_id'));
        } catch (BlogNotFound) {
            abort(404);
        } catch (TemplateNotFound) {
            return back()->withErrors(['template_id' => 'This template is not available.']);
        }

        return redirect()
            ->route('blogs.design.edit', $blog)
            ->with('status', 'Template applied to your draft. Customize it, then publish.');
    }

    public function saveDraft(int $blog, SaveDesignDraftRequest $request, SaveDesignDraftService $save): RedirectResponse
    {
        try {
            $save->execute(
                (int) $request->user()->id,
                $blog,
                TemplateDesignTokens::fromArray($request->validated()),
            );
        } catch (BlogNotFound) {
            abort(404);
        } catch (DomainException $exception) {
            return back()->withErrors(['general' => $exception->getMessage()])->withInput();
        }

        return redirect()->route('blogs.design.edit', $blog)->with('status', 'Draft saved.');
    }

    public function publish(int $blog, Request $request, PublishDesignService $publish): RedirectResponse
    {
        try {
            $publish->execute((int) $request->user()->id, $blog);
        } catch (BlogNotFound) {
            abort(404);
        } catch (DomainException $exception) {
            return back()->withErrors(['general' => $exception->getMessage()]);
        }

        return redirect()->route('blogs.design.edit', $blog)->with('status', 'Design published.');
    }
}
