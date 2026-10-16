<?php

declare(strict_types=1);

namespace App\Presentation\Web\Controllers;

use App\Application\Templates\Contracts\TemplateRepository;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

/**
 * Public template gallery: users choose a template to apply to their blog.
 */
final class TemplateGalleryController extends Controller
{
    public function __invoke(TemplateRepository $templates): View
    {
        return view('templates.gallery', [
            'templates' => $templates->published(),
        ]);
    }
}
