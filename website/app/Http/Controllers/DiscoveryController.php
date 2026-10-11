<?php

namespace App\Http\Controllers;

use App\Modules\Platform\Queries\PublicSettings;
use App\Modules\PublicSite\Queries\DiscoverBlogs;
use Illuminate\Http\Request;

final class DiscoveryController extends Controller
{
    public function index(Request $request, DiscoverBlogs $query)
    {
        abort_unless(app(PublicSettings::class)->get()['discoveryEnabled'], 404);
        $data = $request->validate(['q' => 'nullable|string|max:160', 'topic' => 'nullable|integer']);

        return view('public.discovery', $query->search($data['q'] ?? '', $data['topic'] ?? null));
    }
}
