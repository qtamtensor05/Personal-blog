<?php

namespace App\Modules\Blogs\Queries;

use App\Models\User;
use App\Modules\Blogs\OwnerContext;
use Illuminate\Support\Facades\DB;

final class BlogSettingsQuery
{
    public function find(User $actor, int $blog): array
    {
        $row = app(OwnerContext::class)->resolve($actor, $blog);
        $settings = (array) DB::table('blog_settings')->where('blog_id', $blog)->first();

        return array_intersect_key((array) $row + $settings, array_flip(['name', 'description', 'visibility', 'seo_title', 'seo_description', 'posts_per_page', 'logo_media_id', 'favicon_media_id', 'og_media_id']));
    }
}
