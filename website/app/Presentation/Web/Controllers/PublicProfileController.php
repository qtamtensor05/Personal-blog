<?php

declare(strict_types=1);

namespace App\Presentation\Web\Controllers;

use App\Application\Profile\UseCases\ViewPublicProfileService;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class PublicProfileController extends Controller
{
    public function __invoke(int $user, ViewPublicProfileService $profiles): View
    {
        $page = $profiles->execute($user);
        abort_if($page === null, 404);

        return view('profile.show', $page);
    }
}
