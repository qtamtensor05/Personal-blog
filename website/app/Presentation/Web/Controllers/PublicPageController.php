<?php

declare(strict_types=1);

namespace App\Presentation\Web\Controllers;

use App\Application\Blogs\Exceptions\BlogNotFound;
use App\Application\Blogs\UseCases\ViewPublicBlog\ViewPublicBlogService;
use App\Application\Content\Contracts\PageRepository;
use App\Application\Media\Contracts\MediaAssetRepository;
use App\Presentation\Web\Rendering\TiptapHtmlRenderer;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class PublicPageController extends Controller
{
    public function __invoke(
        string $slug,
        string $page,
        Request $request,
        ViewPublicBlogService $viewBlog,
        PageRepository $pages,
        MediaAssetRepository $media,
        TiptapHtmlRenderer $renderer,
    ): View {
        try {
            // Same blog visibility rules as the blog home.
            $blogView = $viewBlog->execute(
                $slug,
                $request->user() ? (int) $request->user()->id : null,
            );
        } catch (BlogNotFound) {
            abort(404);
        }

        // Only PUBLISHED pages; draft_data is never rendered.
        $record = $pages->findPublishedBySlug($blogView->blog->id, $page) ?? abort(404);

        return view('public.page', [
            'page' => $blogView,
            'staticPage' => $record,
            'html' => $renderer->render(
                $record->content,
                $media->readyAssetsForBlog($blogView->blog->id, $record->content->mediaIds()),
            ),
        ]);
    }
}
