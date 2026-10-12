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

        $allowed = [
            // from blogs table
            'name', 'slug', 'description', 'visibility', 'discoverable',
            // from blog_settings table
            'locale', 'timezone', 'seo_title', 'seo_description', 'posts_per_page',
            'logo_media_id', 'favicon_media_id', 'og_media_id', 'social_links',
            // platform-controlled (read-only, not editable by owner):
            // storage_quota_bytes, post_limit → excluded
        ];

        $merged = (array) $row + $settings;

        $result = array_intersect_key($merged, array_flip($allowed));

        // Decode social_links JSON for the form
        if (isset($result['social_links']) && is_string($result['social_links'])) {
            $result['social_links'] = json_decode($result['social_links'], true) ?? [];
        }
        if (! isset($result['social_links']) || ! is_array($result['social_links'])) {
            $result['social_links'] = [];
        }

        return $result;
    }
}
