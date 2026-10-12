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
            ->when(request('q'), fn ($q) => $q->where('title', 'like', '%'.mb_substr((string) request('q'), 0, 160).'%'))
            ->when(request('category'), fn ($q) => $q->whereIn('id', DB::table('post_categories')->select('post_id')->where('blog_id', $blog->id)->where('category_id', request('category'))))
            ->when(request('tag'), fn ($q) => $q->whereIn('id', DB::table('post_tags')->select('post_id')->where('blog_id', $blog->id)->where('tag_id', request('tag'))))
            ->orderByDesc('published_at')
            ->paginate((int) (DB::table('blog_settings')->where('blog_id', $blog->id)->value('posts_per_page') ?: 10))->withQueryString();

        $design = json_decode(DB::table('blog_designs')->where('blog_id', $blog->id)->value('published_data') ?? '{}', true);
        $pages = DB::table('pages')->where(['blog_id' => $blog->id, 'status' => 'PUBLISHED'])->whereNull('deleted_at')->get(['title', 'slug']);
        $categories = DB::table('categories')->where('blog_id', $blog->id)->where('is_active', true)->get();
        $tags = DB::table('tags')->where('blog_id', $blog->id)->get();

        return view('public.blog', compact('blog', 'posts', 'categories', 'tags') + $this->shell($blog));
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

        return view('public.post', compact('blog', 'post', 'content') + $this->shell($blog));
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

        return view('public.page', compact('blog', 'page', 'content') + $this->shell($blog));
    }

    private function blog(string $slug): object
    {
        $blog = DB::table('blogs')
            ->where(['slug' => $slug, 'status' => 'PUBLISHED'])
            ->whereNull('deleted_at')
            ->first();
        abort_unless($blog, 404);
        abort_if($blog->visibility === 'PRIVATE' && (! auth()->check() || (int) auth()->id() !== (int) $blog->owner_user_id || ! auth()->user()->canAccessApplication()), 404);

        return $blog;
    }

    private function shell(object $blog): array
    {
        $design = json_decode(DB::table('blog_designs')->where('blog_id', $blog->id)->value('published_data') ?? '{}', true);
        $keys = data_get($design, 'navigation.pageKeys');
        $pages = DB::table('pages')->where(['blog_id' => $blog->id, 'status' => 'PUBLISHED'])->whereNull('deleted_at')->when(is_array($keys), fn ($q) => $q->whereIn('page_key', $keys))->get(['title', 'slug']);
        $settings = DB::table('blog_settings')->where('blog_id', $blog->id)->first();
        $locale = in_array($settings?->locale, ['vi', 'en'], true) ? $settings->locale : 'vi';
        $links = json_decode($settings?->social_links ?? '{}', true);
        // Existing data may predate URL validation: never render executable URLs.
        $socialLinks = array_filter(
            array_intersect_key(is_array($links) ? $links : [], array_flip(['twitter', 'facebook', 'instagram', 'youtube', 'github', 'linkedin'])),
            fn ($url) => is_string($url) && filter_var($url, FILTER_VALIDATE_URL)
                && in_array(strtolower(parse_url($url, PHP_URL_SCHEME) ?? ''), ['http', 'https'], true),
        );
        $logoId = $settings?->logo_media_id;
        $logo = DB::table('media_assets')->where(['id' => $logoId, 'blog_id' => $blog->id, 'status' => 'READY'])->whereNull('deleted_at')->value('secure_url');

        return compact('design', 'pages', 'logo', 'locale', 'socialLinks');
    }
}
