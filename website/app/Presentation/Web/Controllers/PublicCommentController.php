<?php

declare(strict_types=1);

namespace App\Presentation\Web\Controllers;

use App\Application\Blogs\Exceptions\BlogNotFound;
use App\Application\Blogs\UseCases\ViewPublicBlog\ViewPublicBlogService;
use App\Application\Comments\Contracts\CommentManager;
use App\Application\Comments\Contracts\CommentReader;
use App\Application\Content\Contracts\PostRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class PublicCommentController extends Controller
{
    public function index(string $slug, string $post, Request $request, ViewPublicBlogService $blogs, PostRepository $posts, CommentReader $reader): JsonResponse
    {
        [$view, $record] = $this->target($slug, $post, $request, $blogs, $posts);

        return response()->json($reader->roots($record->id, $view->blog->ownerUserId, $request->user(), (int) $request->integer('page', 1)));
    }

    public function fragment(string $slug, string $post, int $comment, Request $request, ViewPublicBlogService $blogs, PostRepository $posts, CommentReader $reader): JsonResponse
    {
        [$view, $record] = $this->target($slug, $post, $request, $blogs, $posts);
        $item = $reader->find($record->id, $view->blog->ownerUserId, $comment, $request->user());
        abort_unless($item !== null, 404);

        return response()->json([
            'comment_id' => $comment,
            'parent_id' => $item['parent_id'],
            'html' => view('public.partials.comment-item', ['comment' => $item, 'blog' => $view->blog, 'post' => $record])->render(),
        ]);
    }

    public function replies(string $slug, string $post, int $comment, Request $request, ViewPublicBlogService $blogs, PostRepository $posts, CommentReader $reader): JsonResponse
    {
        [$view, $record] = $this->target($slug, $post, $request, $blogs, $posts);

        $payload = $reader->replies($record->id, $view->blog->ownerUserId, $comment, $request->user(), (int) $request->integer('page', 1));
        $payload['html'] = collect($payload['items'])->mapWithKeys(fn (array $item): array => [
            (string) $item['id'] => view('public.partials.comment-item', ['comment' => $item, 'blog' => $view->blog, 'post' => $record])->render(),
        ])->all();

        return response()->json($payload);
    }

    public function store(string $slug, string $post, Request $request, ViewPublicBlogService $blogs, PostRepository $posts, CommentManager $manager, CommentReader $reader): JsonResponse|RedirectResponse
    {
        [$view, $record] = $this->target($slug, $post, $request, $blogs, $posts);
        $data = $request->validate(['body' => ['required', 'string', 'max:2000'], 'parent_id' => ['nullable', 'integer', 'min:1']]);
        $parentId = ($data['parent_id'] ?? null) === null ? null : (int) $data['parent_id'];
        $commentId = $manager->create($request->user(), $record->id, $view->blog->ownerUserId, (string) $data['body'], $parentId);

        if ($request->expectsJson()) {
            $comment = $reader->find($record->id, $view->blog->ownerUserId, $commentId, $request->user());

            return response()->json([
                'comment_id' => $commentId,
                'parent_id' => $parentId,
                'html' => view('public.partials.comment-item', ['comment' => $comment, 'blog' => $view->blog, 'post' => $record])->render(),
            ], 201);
        }

        return back()->with('comment_status', 'Đã gửi bình luận.');
    }

    public function update(string $slug, string $post, int $comment, Request $request, ViewPublicBlogService $blogs, PostRepository $posts, CommentManager $manager, CommentReader $reader): JsonResponse|RedirectResponse
    {
        [$view, $record] = $this->target($slug, $post, $request, $blogs, $posts);
        $data = $request->validate(['body' => ['required', 'string', 'max:2000']]);
        $manager->edit($request->user(), $record->id, $comment, $data['body']);

        if ($request->expectsJson()) {
            return $this->actionResponse($request, $view, $record, $reader, $comment, 'edit', 'Đã cập nhật bình luận.');
        }

        return back()->with('comment_status', 'Đã cập nhật bình luận.');
    }

    public function destroy(string $slug, string $post, int $comment, Request $request, ViewPublicBlogService $blogs, PostRepository $posts, CommentManager $manager, CommentReader $reader): JsonResponse|RedirectResponse
    {
        [$view, $record] = $this->target($slug, $post, $request, $blogs, $posts);
        $manager->remove($request->user(), $record->id, $comment);

        if ($request->expectsJson()) {
            return $this->actionResponse($request, $view, $record, $reader, $comment, 'delete', 'Đã xóa bình luận.');
        }

        return back()->with('comment_status', 'Đã xóa bình luận.');
    }

    public function hide(string $slug, string $post, int $comment, Request $request, ViewPublicBlogService $blogs, PostRepository $posts, CommentManager $manager, CommentReader $reader): JsonResponse|RedirectResponse
    {
        [$view, $record] = $this->target($slug, $post, $request, $blogs, $posts);
        $manager->hide($request->user(), $record->id, $view->blog->ownerUserId, $comment);

        if ($request->expectsJson()) {
            return $this->actionResponse($request, $view, $record, $reader, $comment, 'hide', 'Đã ẩn bình luận.');
        }

        return back()->with('comment_status', 'Đã ẩn bình luận.');
    }

    public function unhide(string $slug, string $post, int $comment, Request $request, ViewPublicBlogService $blogs, PostRepository $posts, CommentManager $manager, CommentReader $reader): JsonResponse|RedirectResponse
    {
        [$view, $record] = $this->target($slug, $post, $request, $blogs, $posts);
        $manager->unhide($request->user(), $record->id, $view->blog->ownerUserId, $comment);

        if ($request->expectsJson()) {
            return $this->actionResponse($request, $view, $record, $reader, $comment, 'unhide', 'Đã hiện lại bình luận.');
        }

        return back()->with('comment_status', 'Đã hiện lại bình luận.');
    }

    public function report(string $slug, string $post, int $comment, Request $request, ViewPublicBlogService $blogs, PostRepository $posts, CommentManager $manager): JsonResponse|RedirectResponse
    {
        [$view, $record] = $this->target($slug, $post, $request, $blogs, $posts);
        $data = $request->validate(['reason' => ['required', 'string', 'max:2000']]);
        $manager->report($request->user(), $view->blog->id, $record->id, $comment, $data['reason']);

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'action' => 'report',
                'comment_id' => $comment,
                'message' => 'Đã tiếp nhận báo cáo bình luận.',
            ]);
        }

        return back()->with('comment_status', 'Đã tiếp nhận báo cáo bình luận.');
    }

    private function actionResponse(Request $request, object $view, object $record, CommentReader $reader, int $comment, string $action, string $message): JsonResponse
    {
        $item = $reader->find($record->id, $view->blog->ownerUserId, $comment, $request->user());

        return response()->json([
            'ok' => true,
            'action' => $action,
            'comment_id' => $comment,
            'parent_id' => $item['parent_id'] ?? null,
            'html' => $item === null ? null : view('public.partials.comment-item', [
                'comment' => $item,
                'blog' => $view->blog,
                'post' => $record,
            ])->render(),
            'message' => $message,
        ]);
    }

    private function target(string $slug, string $post, Request $request, ViewPublicBlogService $blogs, PostRepository $posts): array
    {
        try {
            $view = $blogs->execute($slug, $request->user()?->id);
        } catch (BlogNotFound) {
            abort(404);
        }

        return [$view, $posts->findPublishedBySlug($view->blog->id, $post) ?? abort(404)];
    }
}
