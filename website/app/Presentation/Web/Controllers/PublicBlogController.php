<?php

declare(strict_types=1);

namespace App\Presentation\Web\Controllers;

use App\Application\Blogs\Exceptions\BlogNotFound;
use App\Application\Blogs\UseCases\ViewPublicBlog\ViewPublicBlogService;
use App\Application\Content\Contracts\PostRepository;
use App\Application\Content\UseCases\ManagePostInteractions\PostEngagementUseCase;
use App\Application\Media\Contracts\MediaAssetRepository;
use App\Domain\Content\ValueObjects\TiptapDocument;
use App\Domain\Templates\TemplateLayouts;
use App\Presentation\Web\Rendering\TiptapHtmlRenderer;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class PublicBlogController extends Controller
{
    public function __invoke(
        string $slug,
        Request $request,
        ViewPublicBlogService $viewBlog,
        MediaAssetRepository $media,
        TiptapHtmlRenderer $renderer,
        PostRepository $posts,
        PostEngagementUseCase $engagement,
    ): View {
        try {
            $page = $viewBlog->execute($slug, $request->user() ? (int) $request->user()->id : null);
        } catch (BlogNotFound) {
            abort(404);
        }

        $mediaUrls = [];
        $documentHtml = [];
        foreach ($page->sections as $section) {
            $content = array_replace($section->props, $page->slotContent[$section->key] ?? []);
            $mediaId = isset($content['mediaId']) ? (int) $content['mediaId'] : 0;
            if ($mediaId > 0 && ($asset = $media->find($mediaId)) !== null) {
                $mediaUrls[$mediaId] = $asset->secureUrl;
            }
            foreach ($content['mediaIds'] ?? [] as $id) {
                if (($asset = $media->find((int) $id)) !== null) {
                    $mediaUrls[(int) $id] = $asset->secureUrl;
                }
            }
            foreach ($content['slides'] ?? [] as $slide) {
                $slideMediaId = (int) ($slide['mediaId'] ?? 0);
                if ($slideMediaId > 0 && ($asset = $media->find($slideMediaId)) !== null) {
                    $mediaUrls[$slideMediaId] = $asset->secureUrl;
                }
            }
            if (
                $section->section === 'document' &&
                isset($content['content']) &&
                is_array($content['content'])
            ) {
                $document = TiptapDocument::fromArray($content['content']);
                $assets = [];
                foreach ($document->mediaIds() as $id) {
                    if (($asset = $media->find($id)) !== null) {
                        $assets[$id] = [
                            'url' => $asset->secureUrl,
                            'width' => $asset->width,
                            'height' => $asset->height,
                        ];
                    }
                }
                $documentHtml[$section->key] = $renderer->render($document, $assets);
            }
        }

        $view = match ($page->layout) {
            TemplateLayouts::LIFESTYLE_EDITORIAL => 'public.templates.lifestyle-editorial.home',
            default => 'public.blog-home',
        };

        $screen = in_array($request->route('screen'), ['posts', 'about', 'contact'], true)
            ? $request->route('screen')
            : 'home';
        $search = '';
        if ($screen === 'posts') {
            $validated = $request->validate([
                'search' => ['nullable', 'string', 'max:100'],
            ]);
            $search = trim((string) ($validated['search'] ?? ''));
        }

        $publicPosts = $posts->publicSummariesForBlog(
            $page->blog->id,
            $screen === 'posts' ? null : 24,
            $search !== '' ? $search : null,
        );

        $coverMediaIds = array_values(
            array_unique(
                array_filter(
                    array_map(static fn ($post): ?int => $post->coverMediaId, array_merge($publicPosts, $page->posts)),
                ),
            ),
        );
        if ($coverMediaIds !== []) {
            foreach (
                $media->readyAssetsForBlog($page->blog->id, $coverMediaIds) as $mediaId => $asset
            ) {
                $mediaUrls[(int) $mediaId] = $asset['url'];
            }
        }

        return view($view, [
            'page' => $page,
            'publicPosts' => $publicPosts,
            'engagement' => $engagement->forVisiblePosts(array_merge($publicPosts, $page->posts)),
            'search' => $search,
            'mediaUrls' => $mediaUrls,
            'documentHtml' => $documentHtml,
            'screen' => $screen,
            'templatePreview' => false,
        ]);
    }
}
