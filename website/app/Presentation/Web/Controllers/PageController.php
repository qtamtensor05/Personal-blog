<?php

declare(strict_types=1);

namespace App\Presentation\Web\Controllers;

use App\Application\BlogDesign\Services\OwnedBlogGuard;
use App\Application\Blogs\Exceptions\BlogNotFound;
use App\Application\Content\Contracts\PageRepository;
use App\Application\Content\UseCases\ManagePages\ManagePagesService;
use App\Application\Media\Contracts\MediaAssetRepository;
use App\Domain\Content\Enums\PostStatus;
use App\Domain\Content\ValueObjects\PostDraft;
use App\Domain\Content\ValueObjects\TiptapDocument;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use InvalidArgumentException;

final class PageController extends Controller
{
    private const SLUG = ['required', 'string', 'max:160', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'];

    public function index(
        int $blog,
        Request $request,
        OwnedBlogGuard $guard,
        PageRepository $pages,
    ): View {
        try {
            $record = $guard->ensure((int) $request->user()->id, $blog);
        } catch (BlogNotFound) {
            abort(404);
        }

        return view('pages.manage.index', ['blog' => $record, 'pages' => $pages->forBlog($blog)]);
    }

    public function store(
        int $blog,
        Request $request,
        ManagePagesService $service,
    ): RedirectResponse {
        // page_key is varchar(80), and it is taken from the slug at creation.
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'slug' => ['required', 'string', 'max:80', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
        ]);

        try {
            $id = $service->create(
                (int) $request->user()->id,
                $blog,
                $data['slug'],
                $data['title'],
            );
        } catch (BlogNotFound) {
            abort(404);
        } catch (DomainException|InvalidArgumentException $exception) {
            return back()
                ->withErrors(['general' => $exception->getMessage()])
                ->withInput();
        }

        return redirect()->route('blogs.pages.edit', [$blog, $id]);
    }

    public function edit(
        int $blog,
        int $page,
        Request $request,
        OwnedBlogGuard $guard,
        PageRepository $pages,
        MediaAssetRepository $media,
    ): View {
        try {
            $record = $guard->ensure((int) $request->user()->id, $blog);
        } catch (BlogNotFound) {
            abort(404);
        }

        $pageRecord = $pages->find($blog, $page) ?? abort(404);
        $editable = $pageRecord->editable();

        return view('pages.manage.edit', [
            'blog' => $record,
            'page' => $pageRecord,
            'editable' => $editable,
            'mediaUrls' => array_map(
                fn (array $asset) => $asset['url'],
                $media->readyAssetsForBlog($blog, $editable->content->mediaIds()),
            ),
        ]);
    }

    public function saveDraft(
        int $blog,
        int $page,
        Request $request,
        ManagePagesService $service,
    ): JsonResponse {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'content' => ['required', 'array'],
        ]);

        try {
            $service->saveDraft(
                (int) $request->user()->id,
                $blog,
                $page,
                new PostDraft($data['title'], null, TiptapDocument::fromArray($data['content'])),
            );
        } catch (BlogNotFound) {
            return new JsonResponse(['message' => 'Not found.'], 404);
        } catch (DomainException|InvalidArgumentException $exception) {
            return new JsonResponse(['message' => $exception->getMessage()], 422);
        }

        return new JsonResponse(['saved_at' => now()->toAtomString()]);
    }

    public function updateMeta(
        int $blog,
        int $page,
        Request $request,
        ManagePagesService $service,
    ): RedirectResponse {
        $data = $request->validate([
            'slug' => self::SLUG,
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:500'],
        ]);

        return $this->run(
            $blog,
            $page,
            fn () => $service->updateMeta(
                (int) $request->user()->id,
                $blog,
                $page,
                $data['slug'],
                $data['seo_title'] ?? null,
                $data['seo_description'] ?? null,
            ),
            'Page details saved.',
        );
    }

    public function publish(
        int $blog,
        int $page,
        Request $request,
        ManagePagesService $service,
    ): RedirectResponse {
        return $this->run(
            $blog,
            $page,
            fn () => $service->publish((int) $request->user()->id, $blog, $page),
            'Page published.',
        );
    }

    public function changeStatus(
        int $blog,
        int $page,
        Request $request,
        ManagePagesService $service,
    ): RedirectResponse {
        $data = $request->validate(['status' => ['required', 'in:DRAFT,ARCHIVED']]);

        return $this->run(
            $blog,
            $page,
            fn () => $service->changeStatus(
                (int) $request->user()->id,
                $blog,
                $page,
                PostStatus::from($data['status']),
            ),
            'Page status updated.',
        );
    }

    public function destroy(
        int $blog,
        int $page,
        Request $request,
        ManagePagesService $service,
    ): RedirectResponse {
        try {
            $service->delete((int) $request->user()->id, $blog, $page);
        } catch (BlogNotFound) {
            abort(404);
        } catch (DomainException $exception) {
            return back()->withErrors(['general' => $exception->getMessage()]);
        }

        return redirect()->route('blogs.pages.index', $blog)->with('status', 'Page deleted.');
    }

    private function run(int $blog, int $page, callable $action, string $message): RedirectResponse
    {
        try {
            $action();
        } catch (BlogNotFound) {
            abort(404);
        } catch (DomainException $exception) {
            return back()
                ->withErrors(['general' => $exception->getMessage()])
                ->withInput();
        }

        return redirect()
            ->route('blogs.pages.edit', [$blog, $page])
            ->with('status', $message);
    }
}
