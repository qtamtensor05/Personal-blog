<?php

declare(strict_types=1);

namespace App\Presentation\Web\Controllers;

use App\Application\BlogDesign\Contracts\BlogDesignRepository;
use App\Application\BlogDesign\Services\OwnedBlogGuard;
use App\Application\BlogDesign\UseCases\ApplyTemplate\ApplyTemplateService;
use App\Application\BlogDesign\UseCases\PublishDesign\PublishDesignService;
use App\Application\BlogDesign\UseCases\SaveDesignDraft\SaveDesignDraftService;
use App\Application\Blogs\Exceptions\BlogNotFound;
use App\Application\Media\Contracts\MediaAssetRepository;
use App\Domain\Content\ValueObjects\TiptapDocument;
use App\Presentation\Web\Rendering\TiptapHtmlRenderer;
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
        MediaAssetRepository $media,
        TiptapHtmlRenderer $renderer,
    ): View {
        try {
            $record = $guard->ensure((int) $request->user()->id, $blog);
        } catch (BlogNotFound) {
            abort(404);
        }

        $design = $designs->find($blog);
        $sections = $design?->draftData?->sections ?? $design?->publishedData?->sections ?? [];
        $mediaUrls = [];
        $documentHtml = [];
        foreach ($sections as $section) {
            $mediaId = isset($section->props['mediaId']) ? (int) $section->props['mediaId'] : 0;
            if ($mediaId > 0 && ($asset = $media->find($mediaId)) !== null) {
                $mediaUrls[$mediaId] = $asset->secureUrl;
            }
            if ($section->section === 'document' && isset($section->props['content']) && is_array($section->props['content'])) {
                $document = TiptapDocument::fromArray($section->props['content']);
                $assets = [];
                foreach ($document->mediaIds() as $id) {
                    if (($asset = $media->find($id)) !== null) {
                        $assets[$id] = ['url' => $asset->secureUrl, 'width' => $asset->width, 'height' => $asset->height];
                    }
                }
                $documentHtml[$section->key] = $renderer->render($document, $assets);
            }
        }

        return view('blogs.design', [
            'blog' => $record,
            'design' => $design,
            'templates' => $templates->published(),
            'mediaUrls' => $mediaUrls,
            'documentHtml' => $documentHtml,
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
