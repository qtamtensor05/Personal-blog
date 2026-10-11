<?php

namespace App\Modules\PublicSite\Queries;

use Illuminate\Support\Facades\DB;

final class DiscoverBlogs
{
    public function search(string $q = '', ?int $topic = null): array
    {
        $query = DB::table('blogs')->where(['status' => 'PUBLISHED', 'visibility' => 'PUBLIC', 'discoverable' => true])->whereNull('deleted_at');
        if ($q !== '') {
            $query->where(fn ($b) => $b->where('name', 'like', '%'.$q.'%')->orWhere('description', 'like', '%'.$q.'%'));
        }
        if ($topic) {
            $query->whereIn('id', DB::table('blog_topic_assignments')->select('blog_id')->where('topic_id', $topic));
        }

        return ['items' => $query->orderByDesc('published_at')->paginate(12)->withQueryString(), 'topics' => DB::table('blog_topics')->where('is_active', true)->get()];
    }
}
