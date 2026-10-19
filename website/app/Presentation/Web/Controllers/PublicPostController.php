<?php

declare(strict_types=1);

namespace App\Presentation\Web\Controllers;

use App\Application\Blogs\Exceptions\BlogNotFound;
use App\Application\Content\UseCases\ViewPublicPost\ViewPublicPostService;
use App\Presentation\Web\Rendering\TiptapHtmlRenderer;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class PublicPostController extends Controller
{
    public function __invoke(
        string $slug,
        string $post,
        Request $request,
        ViewPublicPostService $viewPost,
        TiptapHtmlRenderer $renderer,
    ): View {
        try {
            $result = $viewPost->execute(
                $slug,
                $post,
                $request->user() ? (int) $request->user()->id : null,
            );
        } catch (BlogNotFound) {
            abort(404);
        }

        $record = $result['post'];

        return view('public.post', [
            'page' => $result['page'],
            'post' => $record,
            'html' => $renderer->render($record->content, $result['media']),
            'cover' => $record->coverMediaId !== null
                    ? $result['media'][$record->coverMediaId] ?? null
                    : null,
            'author' => $result['author'],
        ]);
    }
}
