<?php

namespace App\Http\Controllers;

use App\Shared\Contracts\RichTextRenderer;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

final class PublicBlogController extends Controller
{
    public function __construct(private readonly RichTextRenderer $renderer) {}

    public function home(string $blogSlug): View
    {
        $blog = $this->blog($blogSlug);
        $posts = DB::table('posts')
            ->where(['blog_id' => $blog->id, 'status' => 'PUBLISHED'])
            ->whereNull('deleted_at')
            ->orderByDesc('published_at')
            ->paginate(10);

        return view('public.blog', compact('blog', 'posts'));
    }

    public function post(string $blogSlug, string $postSlug): View
    {
        $blog = $this->blog($blogSlug);
        $post = DB::table('posts')
            ->where(['blog_id' => $blog->id, 'slug' => $postSlug, 'status' => 'PUBLISHED'])
            ->whereNull('deleted_at')
            ->first();
        abort_unless($post, 404);

        $content = $this->renderer->render(
            json_decode($post->content_json, true, 512, JSON_THROW_ON_ERROR),
            (int) $blog->id,
        );

        return view('public.post', compact('blog', 'post', 'content'));
    }

    public function page(string $blogSlug, string $pageSlug): View
    {
        $blog = $this->blog($blogSlug);
        $page = DB::table('pages')
            ->where(['blog_id' => $blog->id, 'slug' => $pageSlug, 'status' => 'PUBLISHED'])
            ->whereNull('deleted_at')
            ->first();
        abort_unless($page, 404);

        $content = $this->renderer->render(
            json_decode($page->content_json, true, 512, JSON_THROW_ON_ERROR),
            (int) $blog->id,
        );

        return view('public.page', compact('blog', 'page', 'content'));
    }

    private function blog(string $slug): object
    {
        $blog = DB::table('blogs')
            ->where(['slug' => $slug, 'status' => 'PUBLISHED'])
            ->whereIn('visibility', ['PUBLIC', 'UNLISTED'])
            ->whereNull('deleted_at')
            ->first();
        abort_unless($blog, 404);

        return $blog;
    }
}
