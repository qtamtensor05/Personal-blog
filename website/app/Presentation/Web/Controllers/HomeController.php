<?php

declare(strict_types=1);

namespace App\Presentation\Web\Controllers;

use App\Application\Content\UseCases\GetHomeContent\GetHomeContentUseCase;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class HomeController extends Controller
{
    public function __invoke(GetHomeContentUseCase $home): View
    {
        $content = $home->execute();

        return view('pages.welcome', [
            'featuredPosts' => $content->featuredPosts,
            'recentPosts' => $content->recentPosts,
        ]);
    }
}
