<?php

declare(strict_types=1);

namespace App\Presentation\Web\Controllers;

use App\Application\Blogs\Exceptions\BlogNotFound;
use App\Application\Blogs\UseCases\ViewPublicBlog\ViewPublicBlogService;
use App\Application\Media\Contracts\MediaAssetRepository;
use App\Domain\Content\ValueObjects\TiptapDocument;
use App\Presentation\Web\Rendering\TiptapHtmlRenderer;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class PublicBlogController extends Controller
{
    public function __invoke(string $slug, Request $request, ViewPublicBlogService $viewBlog, MediaAssetRepository $media, TiptapHtmlRenderer $renderer): View
    {
        try {
            $page = $viewBlog->execute($slug, $request->user() ? (int) $request->user()->id : null);
        } catch (BlogNotFound) {
            abort(404);
        }

        $mediaUrls = [];
        $documentHtml = [];
        foreach ($page->sections as $section) {
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

        return view('public.blog-home', ['page' => $page, 'mediaUrls' => $mediaUrls, 'documentHtml' => $documentHtml]);
    }
}
