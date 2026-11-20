<?php

declare(strict_types=1);

namespace App\Presentation\Web\Controllers;

use App\Application\BlogDesign\Services\OwnedBlogGuard;
use App\Application\Blogs\Exceptions\BlogNotFound;
use App\Application\Content\Contracts\PostRepository;
use App\Application\Content\Contracts\TaxonomyRepository;
use App\Application\Content\DTOs\PostLiker;
use App\Application\Content\UseCases\ManagePostInteractions\PostEngagementUseCase;
use App\Application\Content\UseCases\ManagePosts\ManagePostsService;
use App\Application\Media\Contracts\MediaAssetRepository;
use App\Domain\Content\Enums\PostStatus;
use App\Domain\Content\ValueObjects\PostDraft;
use App\Domain\Content\ValueObjects\PostDraftMetadata;
use App\Domain\Content\ValueObjects\TiptapDocument;
use App\Presentation\Web\Support\LocalizedError;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\View\View;
use InvalidArgumentException;

final class PostController extends Controller
{
    private const SLUG = ['required', 'string', 'max:160', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'];

    public function index(
        int $blog,
        Request $request,
        OwnedBlogGuard $guard,
        PostRepository $posts,
        PostEngagementUseCase $engagement,
    ): View {
        try {
            $record = $guard->ensure((int) $request->user()->id, $blog);
        } catch (BlogNotFound) {
            abort(404);
        }

        $listedPosts = $posts->forBlog($blog);

        return view('posts.manage.index', [
            'blog' => $record,
            'posts' => $listedPosts,
            'engagement' => $engagement->forOwnedPosts((int) $request->user()->id, $blog, $listedPosts),
            'deletedPosts' => $posts->deletedForBlog($blog),
        ]);
    }

    public function likers(
        int $blog,
        int $post,
        Request $request,
        PostRepository $posts,
        PostEngagementUseCase $engagement,
    ): JsonResponse|View {
        $data = $request->validate(['page' => ['nullable', 'integer', 'min:1']]);

        try {
            $likers = $engagement->likers((int) $request->user()->id, $blog, $post, (int) ($data['page'] ?? 1));
        } catch (BlogNotFound) {
            abort(404);
        }

        if ($request->expectsJson()) {
            return new JsonResponse([
                'total' => $likers->total,
                'page' => $likers->page,
                'lastPage' => $likers->lastPage(),
                'likers' => array_map(static function (PostLiker $liker): array {
                    $likedAt = Carbon::parse($liker->likedAt);

                    return [
                        'name' => $liker->name,
                        'avatarUrl' => $liker->avatarUrl,
                        'avatarInitial' => mb_strtoupper(mb_substr($liker->name, 0, 1)),
                        'likedAt' => $likedAt->toAtomString(),
                        'likedAtTitle' => $likedAt->format('d/m/Y H:i'),
                        'likedAtTime' => $likedAt->format('H:i'),
                    ];
                }, $likers->likers),
            ]);
        }

        return view('posts.manage.likers', [
            'blogId' => $blog,
            'post' => $posts->find($blog, $post),
            'likers' => $likers,
        ]);
    }

    public function store(
        int $blog,
        Request $request,
        ManagePostsService $service,
        PostRepository $posts,
    ): RedirectResponse {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'slug' => ['nullable', 'string', 'max:160', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
        ]);

        $baseSlug = $data['slug'] ?? null ?: Str::slug($data['title']);
        $baseSlug = $baseSlug !== '' ? $baseSlug : 'post';
        $slug = Str::limit($baseSlug, 150, '');
        $suffix = 2;
        $slugWasAdjusted = false;

        while ($posts->slugExists($blog, $slug)) {
            $slugWasAdjusted = true;
            $slug = Str::limit($baseSlug, 150, '').'-'.$suffix;
            $suffix++;
        }

        try {
            $id = $service->create(
                (int) $request->user()->id,
                $blog,
                $slug ?: Str::slug($data['title']),
                new PostDraft($data['title'], null, TiptapDocument::empty()),
            );
        } catch (BlogNotFound) {
            abort(404);
        } catch (DomainException|InvalidArgumentException $exception) {
            return back()
                ->withErrors(['general' => LocalizedError::message($exception->getMessage())])
                ->withInput();
        }

        $response = redirect()->route('blogs.posts.edit', [$blog, $id]);

        return $slugWasAdjusted
            ? $response->with(
                'status',
                "Slug '{$baseSlug}' đã được dùng hoặc thuộc bài đã xóa; đường dẫn mới là '{$slug}'.",
            )
            : $response;
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
            'mediaAssets' => $media->readyForBlog($blog),
            'categories' => $taxonomy->categories($blog),
            'tags' => $taxonomy->tags($blog),
        ]);
    }

    /**
     * JSON autosave / save from the Tiptap editor.
     */
    public function saveDraft(
        int $blog,
        int $post,
        Request $request,
        ManagePostsService $service,
    ): JsonResponse {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'excerpt' => ['nullable', 'string', 'max:1000'],
            'content' => ['required', 'array'],
            'metadata' => ['sometimes', 'array:slug,seo_title,seo_description'],
            'metadata.slug' => ['nullable', 'string', 'max:160', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'metadata.seo_title' => ['nullable', 'string', 'max:255'],
            'metadata.seo_description' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $service->saveDraft(
                (int) $request->user()->id,
                $blog,
                $post,
                new PostDraft(
                    $data['title'],
                    $data['excerpt'] ?? null,
                    TiptapDocument::fromArray($data['content']),
                    isset($data['metadata']) ? PostDraftMetadata::fromArray($data['metadata']) : null,
                ),
            );
        } catch (BlogNotFound) {
            return new JsonResponse(['message' => __('Not found.')], 404);
        } catch (DomainException|InvalidArgumentException $exception) {
            return new JsonResponse(['message' => LocalizedError::message($exception->getMessage())], 422);
        }

        return new JsonResponse(['saved_at' => now()->toAtomString()]);
    }

    public function updateMeta(
        int $blog,
        int $post,
        Request $request,
        ManagePostsService $service,
    ): RedirectResponse {
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

        return $this->run(
            $blog,
            $post,
            fn () => $service->updateMeta(
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
            ),
            __('Post details saved.'),
        );
    }

    public function publish(
        int $blog,
        int $post,
        Request $request,
        ManagePostsService $service,
    ): RedirectResponse {
        $data = $request->validate(
            [
                'title' => ['required', 'string', 'max:160'],
                'excerpt' => ['nullable', 'string', 'max:1000'],
                'content' => ['required', 'json'],
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
            ],
            [
                'title.required' => __('Please enter the post title in the Title field above.'),
                'title.max' => __('The title may not exceed 160 characters. Shorten it and try again.'),
                'content.required' => __('No post content was received. Enter content in the editor and try publishing again.'),
                'content.json' => __('Content has not synced from the editor. Re-enter the content or reload the page and try again.'),
                'slug.required' => __('Please enter the post slug in the Slug field.'),
                'slug.regex' => __('The slug may only contain lowercase unaccented letters, numbers and hyphens; for example: new-post.'),
                'slug.max' => __('The slug may not exceed 160 characters. Shorten it and try again.'),
                'is_featured.required' => __('The featured post option was not received. Reload the page and try publishing again.'),
                'category_ids.array' => __('The category list is invalid. Please select the categories again.'),
                'tag_ids.array' => __('The tag list is invalid. Please select the tags again.'),
            ],
        );

        try {
            $document = TiptapDocument::fromArray(
                json_decode($data['content'], true, 512, JSON_THROW_ON_ERROR),
            );
        } catch (InvalidArgumentException|\JsonException) {
            return back()
                ->withErrors([
                    'content' => __('The post content is invalid. Re-enter it in the editor and try publishing again.'),
                ])
                ->withInput();
        }

        if (trim($document->plainText()) === '' && $document->mediaIds() === []) {
            return back()
                ->withErrors(['content' => __('Please enter post content before publishing.')])
                ->withInput();
        }

        try {
            $actorId = (int) $request->user()->id;
            $service->saveDraft(
                $actorId,
                $blog,
                $post,
                new PostDraft($data['title'], $data['excerpt'] ?? null, $document, new PostDraftMetadata($data['slug'], $data['seo_title'] ?? null, $data['seo_description'] ?? null)),
            );
            $service->updateMeta(
                $actorId,
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
            );
            $service->publish($actorId, $blog, $post);
        } catch (BlogNotFound) {
            abort(404);
        } catch (DomainException|InvalidArgumentException $exception) {
            return back()
                ->withErrors(['general' => LocalizedError::message($exception->getMessage())])
                ->withInput();
        }

        return redirect()
            ->route('blogs.posts.edit', [$blog, $post])
            ->with('status', __('Post published.'));
    }

    public function changeStatus(
        int $blog,
        int $post,
        Request $request,
        ManagePostsService $service,
    ): RedirectResponse {
        $data = $request->validate(['status' => ['required', 'in:DRAFT,ARCHIVED']]);

        return $this->run(
            $blog,
            $post,
            fn () => $service->changeStatus(
                (int) $request->user()->id,
                $blog,
                $post,
                PostStatus::from($data['status']),
            ),
            __('Post status updated.'),
        );
    }

    public function destroy(
        int $blog,
        int $post,
        Request $request,
        ManagePostsService $service,
    ): RedirectResponse {
        try {
            $service->delete((int) $request->user()->id, $blog, $post);
        } catch (BlogNotFound) {
            abort(404);
        } catch (DomainException $exception) {
            return back()->withErrors(['general' => LocalizedError::message($exception->getMessage())]);
        }

        return redirect()
            ->route('blogs.posts.index', $blog)
            ->with('status', __('Post moved to Recently deleted.'));
    }

    public function restore(
        int $blog,
        int $post,
        Request $request,
        OwnedBlogGuard $guard,
        PostRepository $posts,
    ): RedirectResponse {
        try {
            $guard->ensure((int) $request->user()->id, $blog);
        } catch (BlogNotFound) {
            abort(404);
        }
        $posts->restore($blog, $post);

        return back()->with('status', __('Post restored.'));
    }

    public function forceDestroy(
        int $blog,
        int $post,
        Request $request,
        OwnedBlogGuard $guard,
        PostRepository $posts,
    ): RedirectResponse {
        try {
            $guard->ensure((int) $request->user()->id, $blog);
        } catch (BlogNotFound) {
            abort(404);
        }
        $posts->permanentlyDelete($blog, $post);

        return back()->with('status', __('Post permanently deleted.'));
    }

    private function run(int $blog, int $post, callable $action, string $message): RedirectResponse
    {
        try {
            $action();
        } catch (BlogNotFound) {
            abort(404);
        } catch (DomainException $exception) {
            return back()
                ->withErrors(['general' => LocalizedError::message($exception->getMessage())])
                ->withInput();
        }

        return redirect()
            ->route('blogs.posts.edit', [$blog, $post])
            ->with('status', $message);
    }
}
