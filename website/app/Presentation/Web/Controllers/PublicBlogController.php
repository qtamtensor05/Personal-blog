<?php

declare(strict_types=1);

namespace App\Presentation\Web\Controllers;

use App\Application\Blogs\Exceptions\BlogNotFound;
use App\Application\Blogs\UseCases\ViewPublicBlog\ViewPublicBlogService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class PublicBlogController extends Controller
{
    public function __invoke(string $slug, Request $request, ViewPublicBlogService $viewBlog): View
    {
        try {
            $page = $viewBlog->execute($slug, $request->user() ? (int) $request->user()->id : null);
        } catch (BlogNotFound) {
            abort(404);
        }

        return view('public.blog-home', ['page' => $page]);
    }
}
