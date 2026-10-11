<?php

namespace App\Http\Controllers;

use App\Modules\Blogs\OwnerContext;
use App\Modules\Content\Queries\ContentWorkspace;
use App\Modules\Design\Queries\DesignWorkspace;
use App\Shared\Contracts\RichTextRenderer;

final class PreviewController extends Controller
{
    public function content(int $blog, string $kind, int $id)
    {
        abort_unless(in_array($kind, ['posts', 'pages']), 404);
        $record = app(ContentWorkspace::class)->find(auth()->user(), $blog, $kind, $id);
        $draft = json_decode($record->draft_data ?? '{}', true);
        $data = array_replace((array) $record, $draft);
        $document = is_string($data['content_json']) ? json_decode($data['content_json'], true) : $data['content_json'];
        $content = app(RichTextRenderer::class)->render($document, $blog);

        return response()->view('public.preview-content', ['record' => (object) $data, 'content' => $content])->header('X-Robots-Tag', 'noindex, nofollow')->header('Cache-Control', 'private, no-store');
    }

    public function design(int $blog)
    {
        $record = app(OwnerContext::class)->resolve(auth()->user(), $blog);
        $design = app(DesignWorkspace::class)->find(auth()->user(), $blog);
        abort_unless($design, 404);
        $posts = app(ContentWorkspace::class)->rows(auth()->user(), $blog, 'posts', '', 'PUBLISHED');

        return response()->view('public.preview-design', ['blog' => $record, 'design' => json_decode($design->draft_data ?? $design->published_data, true), 'posts' => $posts])->header('X-Robots-Tag','noindex, nofollow')->header('Cache-Control','private, no-store');
    }
}
