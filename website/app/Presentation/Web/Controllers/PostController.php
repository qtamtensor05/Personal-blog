<?php

declare(strict_types=1);

namespace App\Presentation\Web\Controllers;

use App\Application\BlogDesign\Services\OwnedBlogGuard;
use App\Application\Blogs\Exceptions\BlogNotFound;
use App\Application\Content\Contracts\PostRepository;
use App\Application\Content\Contracts\TaxonomyRepository;
use App\Application\Content\UseCases\ManagePostInteractions\PostEngagementService;
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
        PostEngagementService $engagement,
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
        PostEngagementService $engagement,
    ): View {
        $data = $request->validate(['page' => ['nullable', 'integer', 'min:1']]);

        try {
            $likers = $engagement->likers((int) $request->user()->id, $blog, $post, (int) ($data['page'] ?? 1));
        } catch (BlogNotFound) {
            abort(404);
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
                ->withErrors(['general' => $exception->getMessage()])
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
                ),
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
            'Post details saved.',
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
                'title.required' => 'Vui lòng nhập tiêu đề bài viết ở ô Tiêu đề phía trên.',
                'title.max' => 'Tiêu đề tối đa 160 ký tự. Hãy rút gọn tiêu đề rồi thử lại.',
                'content.required' => 'Chưa nhận được nội dung bài viết. Hãy nhập nội dung trong khung soạn thảo rồi thử xuất bản lại.',
                'content.json' => 'Nội dung chưa được đồng bộ từ trình soạn thảo. Hãy nhập lại nội dung hoặc tải lại trang rồi thử lại.',
                'slug.required' => 'Vui lòng nhập đường dẫn bài viết ở trường Đường dẫn.',
                'slug.regex' => 'Đường dẫn chỉ gồm chữ thường không dấu, số và dấu gạch ngang; ví dụ: bai-viet-moi.',
                'slug.max' => 'Đường dẫn tối đa 160 ký tự. Hãy rút gọn rồi thử lại.',
                'is_featured.required' => 'Không nhận được lựa chọn bài viết nổi bật. Hãy tải lại trang rồi thử xuất bản lại.',
                'category_ids.array' => 'Danh sách chuyên mục không hợp lệ. Hãy chọn lại chuyên mục.',
                'tag_ids.array' => 'Danh sách thẻ không hợp lệ. Hãy chọn lại thẻ.',
            ],
        );

        try {
            $document = TiptapDocument::fromArray(
                json_decode($data['content'], true, 512, JSON_THROW_ON_ERROR),
            );
        } catch (InvalidArgumentException|\JsonException) {
            return back()
                ->withErrors([
                    'content' => 'Nội dung bài viết không hợp lệ. Hãy nhập lại trong khung soạn thảo rồi thử xuất bản.',
                ])
                ->withInput();
        }

        if (trim($document->plainText()) === '' && $document->mediaIds() === []) {
            return back()
                ->withErrors(['content' => 'Vui lòng nhập nội dung bài viết trước khi xuất bản.'])
                ->withInput();
        }

        try {
            $actorId = (int) $request->user()->id;
            $service->saveDraft(
                $actorId,
                $blog,
                $post,
                new PostDraft($data['title'], $data['excerpt'] ?? null, $document),
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
                ->withErrors(['general' => $exception->getMessage()])
                ->withInput();
        }

        return redirect()
            ->route('blogs.posts.edit', [$blog, $post])
            ->with('status', 'Post published.');
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
            'Post status updated.',
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
            return back()->withErrors(['general' => $exception->getMessage()]);
        }

        return redirect()
            ->route('blogs.posts.index', $blog)
            ->with('status', 'Post moved to Recently deleted.');
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

        return back()->with('status', 'Post restored.');
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

        return back()->with('status', 'Post permanently deleted.');
    }

    private function run(int $blog, int $post, callable $action, string $message): RedirectResponse
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
            ->route('blogs.posts.edit', [$blog, $post])
            ->with('status', $message);
    }
}
