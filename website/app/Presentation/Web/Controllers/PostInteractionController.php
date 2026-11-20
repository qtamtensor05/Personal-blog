<?php

declare(strict_types=1);

namespace App\Presentation\Web\Controllers;

use App\Application\Blogs\Exceptions\BlogNotFound;
use App\Application\Content\UseCases\ManagePostInteractions\PostInteractionsUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class PostInteractionController extends Controller
{
    public function like(string $slug, string $post, Request $request, PostInteractionsUseCase $interactions): RedirectResponse|JsonResponse
    {
        try {
            $interactions->setLike((int) $request->user()->id, $slug, $post, true);
        } catch (BlogNotFound) {
            abort(404);
        }

        if ($request->expectsJson()) {
            return response()->json(['active' => true]);
        }

        return redirect()->route('public.post', [$slug, $post]);
    }

    public function unlike(string $slug, string $post, Request $request, PostInteractionsUseCase $interactions): RedirectResponse|JsonResponse
    {
        try {
            $interactions->setLike((int) $request->user()->id, $slug, $post, false);
        } catch (BlogNotFound) {
            abort(404);
        }

        if ($request->expectsJson()) {
            return response()->json(['active' => false]);
        }

        return redirect()->route('public.post', [$slug, $post]);
    }

    public function bookmark(string $slug, string $post, Request $request, PostInteractionsUseCase $interactions): RedirectResponse|JsonResponse
    {
        try {
            $interactions->setBookmark((int) $request->user()->id, $slug, $post, true);
        } catch (BlogNotFound) {
            abort(404);
        }

        if ($request->expectsJson()) {
            return response()->json(['active' => true]);
        }

        return redirect()->route('public.post', [$slug, $post]);
    }

    public function unbookmark(string $slug, string $post, Request $request, PostInteractionsUseCase $interactions): RedirectResponse|JsonResponse
    {
        try {
            $interactions->setBookmark((int) $request->user()->id, $slug, $post, false);
        } catch (BlogNotFound) {
            abort(404);
        }

        if ($request->expectsJson()) {
            return response()->json(['active' => false]);
        }

        return redirect()->route('public.post', [$slug, $post]);
    }
}
