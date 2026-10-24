<?php

declare(strict_types=1);

namespace App\Presentation\Web\Controllers;

use App\Application\Content\UseCases\ListPublicPosts\ListPublicPostsService;
use App\Application\Content\UseCases\ManagePostInteractions\PostEngagementUseCase;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class PublicPostIndexController extends Controller
{
    public function __invoke(Request $request, ListPublicPostsService $posts, PostEngagementUseCase $engagement): View
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:160'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $page = $posts->execute($validated['search'] ?? null, (int) ($validated['page'] ?? 1));

        return view('pages.posts.index', [
            'posts' => $page,
            'engagement' => $engagement->forVisiblePosts($page->items),
            'search' => trim((string) ($validated['search'] ?? '')),
        ]);
    }
}
