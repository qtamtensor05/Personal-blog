<?php

declare(strict_types=1);

namespace App\Presentation\Web\Controllers;

use App\Application\Content\Contracts\PostRepository;
use App\Application\Media\Contracts\MediaAssetRepository;
use App\Application\Templates\Contracts\TemplateRepository;
use App\Domain\Content\ValueObjects\TiptapDocument;
use App\Domain\Templates\TemplateLayouts;
use App\Presentation\Web\Rendering\TiptapHtmlRenderer;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class TemplatePreviewController extends Controller
{
    public function __invoke(
        string $slug,
        Request $request,
        TemplateRepository $templates,
        MediaAssetRepository $media,
        PostRepository $posts,
        TiptapHtmlRenderer $renderer,
    ): View {
        $template = collect($templates->all())->firstWhere('slug', $slug);

        abort_if($template === null, 404);

        $data = $template->draftData ?? $template->publishedData;

        abort_if($data === null, 404);

        $mediaUrls = [];
        $documentHtml = [];
        foreach ($data->sections as $section) {
            $mediaId = isset($section->props['mediaId']) ? (int) $section->props['mediaId'] : 0;
            if ($mediaId > 0 && ($asset = $media->find($mediaId)) !== null) {
                $mediaUrls[$mediaId] = $asset->secureUrl;
            }
            if (
                $section->section === 'document' &&
                isset($section->props['content']) &&
                is_array($section->props['content'])
            ) {
                $document = TiptapDocument::fromArray($section->props['content']);
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

        if ($data->layout === TemplateLayouts::LIFESTYLE_EDITORIAL) {
            return view('public.templates.lifestyle-editorial.home', [
                'page' => (object) [
                    'blog' => (object) [
                        'name' => $template->name,
                        'description' => $template->description,
                        'slug' => $template->slug,
                    ],
                    'settings' => (object) [
                        'locale' => 'vi',
                        'seoTitle' => $template->name,
                        'seoDescription' => $template->description,
                        'socialLinks' => [],
                    ],
                    'sections' => $data->sections,
                    'slotContent' => [],
                    'posts' => [],
                    'categories' => [],
                    'noIndex' => true,
                    'isOwnerPreview' => false,
                ],
                'publicPosts' => [],
                'mediaUrls' => $mediaUrls,
                'documentHtml' => $documentHtml,
                'screen' => in_array($request->query('screen'), ['posts', 'about', 'contact'], true)
                    ? $request->query('screen')
                    : 'home',
                'templatePreview' => true,
            ]);
        }

        return view('templates.preview', [
            'template' => $template,
            'data' => $data,
            'blog' => (object) [
                'name' => $template->name,
                'description' => $template->description,
                'slug' => $template->slug,
            ],
            'mediaUrls' => $mediaUrls,
            'documentHtml' => $documentHtml,
        ]);
    }
}
