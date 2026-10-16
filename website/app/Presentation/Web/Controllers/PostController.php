<?php

declare(strict_types=1);

namespace App\Presentation\Web\Controllers;

use App\Application\BlogDesign\Services\OwnedBlogGuard;
use App\Application\Blogs\Exceptions\BlogNotFound;
use App\Application\Content\Contracts\PostRepository;
use App\Application\Content\Contracts\TaxonomyRepository;
use App\Application\Content\UseCases\ManagePosts\ManagePostsService;
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

final class PostController extends Controller
{
    private const SLUG = ['required', 'string', 'max:160', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'];

    public function index(int $blog, Request $request, OwnedBlogGuard $guard, PostRepository $posts): View
    {
        try {
            $record = $guard->ensure((int) $request->user()->id, $blog);
        } catch (BlogNotFound) {
            abort(404);
        }

        return view('posts.manage.index', ['blog' => $record, 'posts' => $posts->forBlog($blog)]);
    }

    public function store(int $blog, Request $request, ManagePostsService $service): RedirectResponse
    {
        $data = $request->validate(['title' => ['required', 'string', 'max:160'], 'slug' => self::SLUG]);

        try {
            $id = $service->create(
                (int) $request->user()->id,
                $blog,
                $data['slug'],
                new PostDraft($data['title'], null, TiptapDocument::empty()),
            );
        } catch (BlogNotFound) {
            abort(404);
        } catch (DomainException|InvalidArgumentException $exception) {
            return back()->withErrors(['general' => $exception->getMessage()])->withInput();
        }

        return redirect()->route('blogs.posts.edit', [$blog, $id]);
    }

    public function edit(
        int $blog,
        int $post,
        Request $request,
        OwnedBlogGuard $guard,
        PostRepository $posts,
        TaxonomyRepository $taxonomy,
        MediaAssetRepository $media,
    ): View {
        try {
            $record = $guard->ensure((int) $request->user()->id, $blog);
        } catch (BlogNotFound) {
            abort(404);
        }

        $postRecord = $posts->find($blog, $post) ?? abort(404);
        $editable = $postRecord->editable();

        return view('posts.manage.edit', [
            'blog' => $record,
            'post' => $postRecord,
            'editable' => $editable,
            // Lets the editor show images already in the document.
            'mediaUrls' => array_map(
                fn (array $asset) => $asset['url'],
                $media->readyAssetsForBlog($blog, $editable->content->mediaIds()),
            ),
            'categories' => $taxonomy->categories($blog),
            'tags' => $taxonomy->tags($blog),
        ]);
    }

    /**
     * JSON autosave / save from the Tiptap editor.
     */
    public function saveDraft(int $blog, int $post, Request $request, ManagePostsService $service): JsonResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'excerpt' => ['nullable', 'string', 'max:1000'],
            'content' => ['required', 'array'],
        ]);

        try {
            $service->saveDraft(
                (int) $request->user()->id,
                $blog,
                $post,
                new PostDraft($data['title'], $data['excerpt'] ?? null, TiptapDocument::fromArray($data['content'])),
            );
        } catch (BlogNotFound) {
            return new JsonResponse(['message' => 'Not found.'], 404);
        } catch (DomainException|InvalidArgumentException $exception) {
            return new JsonResponse(['message' => $exception->getMessage()], 422);
        }

        return new JsonResponse(['saved_at' => now()->toAtomString()]);
    }

    public function updateMeta(int $blog, int $post, Request $request, ManagePostsService $service): RedirectResponse
    {
        $data = $request->validate([
            'slug' => self::SLUG,
            'cover_media_id' => ['nullable', 'integer', 'min:1'],
            'og_media_id' => ['nullable', 'integer', 'min:1'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:500'],
            'is_featured' => ['required', 'boolean'],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['integer', 'min:1'],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['integer', 'min:1'],
        ]);

        return $this->run($blog, $post, fn () => $service->updateMeta(
            (int) $request->user()->id,
            $blog,
            $post,
            $data['slug'],
            isset($data['cover_media_id']) ? (int) $data['cover_media_id'] : null,
            isset($data['og_media_id']) ? (int) $data['og_media_id'] : null,
            $data['seo_title'] ?? null,
            $data['seo_description'] ?? null,
            (bool) $data['is_featured'],
            array_map('intval', $data['category_ids'] ?? []),
            array_map('intval', $data['tag_ids'] ?? []),
        ), 'Post details saved.');
    }

    public function publish(int $blog, int $post, Request $request, ManagePostsService $service): RedirectResponse
    {
        return $this->run($blog, $post, fn () => $service->publish((int) $request->user()->id, $blog, $post), 'Post published.');
    }

    public function changeStatus(int $blog, int $post, Request $request, ManagePostsService $service): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', 'in:DRAFT,ARCHIVED']]);

        return $this->run(
            $blog,
            $post,
            fn () => $service->changeStatus((int) $request->user()->id, $blog, $post, PostStatus::from($data['status'])),
            'Post status updated.',
        );
    }

    public function destroy(int $blog, int $post, Request $request, ManagePostsService $service): RedirectResponse
    {
        try {
            $service->delete((int) $request->user()->id, $blog, $post);
        } catch (BlogNotFound) {
            abort(404);
        } catch (DomainException $exception) {
            return back()->withErrors(['general' => $exception->getMessage()]);
        }

        return redirect()->route('blogs.posts.index', $blog)->with('status', 'Post deleted.');
    }

    private function run(int $blog, int $post, callable $action, string $message): RedirectResponse
    {
        try {
            $action();
        } catch (BlogNotFound) {
            abort(404);
        } catch (DomainException $exception) {
            return back()->withErrors(['general' => $exception->getMessage()])->withInput();
        }

        return redirect()->route('blogs.posts.edit', [$blog, $post])->with('status', $message);
    }
}
