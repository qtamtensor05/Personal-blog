<?php

declare(strict_types=1);

namespace App\Presentation\Web\Controllers;

use App\Application\Content\UseCases\ManagePostInteractions\PostInteractionsUseCase;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class BookmarkedPostsController extends Controller
{
    public function __invoke(Request $request, PostInteractionsUseCase $interactions): View
    {
        return view('posts.bookmarks', [
            'posts' => $interactions->bookmarks((int) $request->user()->id),
        ]);
    }
}
