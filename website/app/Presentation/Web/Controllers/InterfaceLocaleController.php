<?php

declare(strict_types=1);

namespace App\Presentation\Web\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class InterfaceLocaleController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $data = $request->validate(['locale' => ['required', 'in:vi,en']]);
        $request->session()->put('interface_locale', $data['locale']);

        return back();
    }
}
