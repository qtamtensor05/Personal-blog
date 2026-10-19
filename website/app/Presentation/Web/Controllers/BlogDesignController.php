<?php

declare(strict_types=1);

namespace App\Presentation\Web\Controllers;

use App\Application\BlogDesign\Contracts\BlogDesignRepository;
use App\Application\BlogDesign\Services\OwnedBlogGuard;
use App\Application\BlogDesign\UseCases\ApplyTemplate\ApplyTemplateService;
use App\Application\BlogDesign\UseCases\PublishRegion\PublishRegionService;
use App\Application\BlogDesign\UseCases\SaveSlotContent\SaveSlotContentService;
use App\Application\Blogs\Exceptions\BlogNotFound;
use App\Application\Media\Contracts\MediaAssetRepository;
use App\Application\Templates\Contracts\TemplateRepository;
use App\Application\Templates\Exceptions\TemplateNotFound;
use App\Presentation\Web\Requests\SaveSlotContentRequest;
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
    ): View {
        try {
            $record = $guard->ensure((int) $request->user()->id, $blog);
        } catch (BlogNotFound) {
            abort(404);
        }

        $design = $designs->find($blog);

        return view('blogs.design', [
            'blog' => $record,
            'design' => $design,
            'templates' => $templates->published(),
            'mediaAssets' => $media->readyForBlog($blog),
            'activeRegion' => in_array(
                $request->query('region'),
                ['header', 'body', 'sidebar', 'footer'],
                true,
            )
                ? (string) $request->query('region')
                : 'body',
        ]);
    }

    public function applyTemplate(
        int $blog,
        Request $request,
        ApplyTemplateService $apply,
    ): RedirectResponse {
        $request->validate(['template_id' => ['required', 'integer', 'min:1']]);

        try {
            $apply->execute(
                (int) $request->user()->id,
                $blog,
                (int) $request->input('template_id'),
            );
        } catch (BlogNotFound) {
            abort(404);
        } catch (TemplateNotFound) {
            return back()->withErrors(['template_id' => 'This template is not available.']);
        } catch (DomainException $exception) {
            return back()->withErrors(['template_id' => $exception->getMessage()]);
        }

        return redirect()
            ->route('blogs.design.edit', $blog)
            ->with(
                'status',
                'Template applied. Its fallback content is ready in your draft preview.',
            );
    }

    public function saveSlotContent(
        int $blog,
        SaveSlotContentRequest $request,
        SaveSlotContentService $save,
        PublishRegionService $publish,
    ): RedirectResponse {
        $region = $request->validated('region') ?? 'body';

        try {
            $save->execute((int) $request->user()->id, $blog, $request->validated('slot_content'));
            if ($request->boolean('publish')) {
                $publish->execute((int) $request->user()->id, $blog, $region);
            }
        } catch (BlogNotFound) {
            abort(404);
        } catch (DomainException $exception) {
            return back()
                ->withErrors(['general' => $exception->getMessage()])
                ->withInput();
        }

        return redirect()
            ->route('blogs.design.edit', ['blog' => $blog, 'region' => $region])
            ->with(
                'status',
                $request->boolean('publish')
                    ? ucfirst($region).' content saved and published.'
                    : ucfirst($region).' content saved as draft.',
            );
    }

    public function publishRegion(
        int $blog,
        string $region,
        Request $request,
        PublishRegionService $publish,
    ): RedirectResponse {
        try {
            $publish->execute((int) $request->user()->id, $blog, $region);
        } catch (BlogNotFound) {
            abort(404);
        } catch (DomainException $exception) {
            return back()->withErrors(['general' => $exception->getMessage()]);
        }

        return redirect()
            ->route('blogs.design.edit', ['blog' => $blog, 'region' => $region])
            ->with('status', ucfirst($region).' content published.');
    }
}
