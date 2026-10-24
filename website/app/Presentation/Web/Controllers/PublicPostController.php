<?php

declare(strict_types=1);

namespace App\Presentation\Web\Controllers;

use App\Application\Blogs\Exceptions\BlogNotFound;
use App\Application\Content\UseCases\ManagePostInteractions\PostEngagementUseCase;
use App\Application\Content\UseCases\ManagePostInteractions\PostInteractionsUseCase;
use App\Application\Content\UseCases\ManagePostInteractions\RecordPostViewUseCase;
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
        PostInteractionsUseCase $interactions,
        PostEngagementUseCase $engagement,
        RecordPostViewUseCase $views,
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
        $views->execute($record->id, $result['page']->blog->ownerUserId, $request->user()?->id, $request->session()->getId());
        $postEngagement = $engagement->forVisiblePosts([$record])[$record->id];
        $canViewLikers = $request->user() !== null
            && (int) $request->user()->id === $result['page']->blog->ownerUserId;

        return view('public.post', [
            'page' => $result['page'],
            'post' => $record,
            'html' => $renderer->render($record->content, $result['media']),
            'cover' => $record->coverMediaId !== null
                    ? $result['media'][$record->coverMediaId] ?? null
                    : null,
            'author' => $result['author'],
            'interactionState' => $request->user()
                ? $interactions->state((int) $request->user()->id, $record->id)
                : null,
            'postEngagement' => $postEngagement,
            'canViewLikers' => $canViewLikers,
        ]);
    }
}
