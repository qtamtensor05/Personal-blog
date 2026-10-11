<?php

namespace App\Modules\Content\Queries;

use App\Models\User;
use App\Modules\Blogs\OwnerContext;
use Illuminate\Support\Facades\DB;

final class ContentRelations
{
    public function find(User $actor, int $blog, int $post): array
    {
        app(OwnerContext::class)->resolve($actor, $blog);

        return ['category_ids' => DB::table('post_categories')->where(['blog_id' => $blog, 'post_id' => $post])->pluck('category_id')->all(), 'tag_ids' => DB::table('post_tags')->where(['blog_id' => $blog, 'post_id' => $post])->pluck('tag_id')->all()];
    }
}
