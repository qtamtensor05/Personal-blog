<?php

declare(strict_types=1);

namespace App\Infrastructure\Content\Persistence;

use App\Application\Content\Contracts\PostLocationRepository;
use App\Application\Content\DTOs\PostLocation;
use Illuminate\Support\Facades\DB;

final class QueryBuilderPostLocationRepository implements PostLocationRepository
{
    public function findForPost(int $postId): ?PostLocation
    {
        $row = DB::table('posts as post')
            ->join('blogs as blog', 'blog.id', '=', 'post.blog_id')
            ->where('post.id', $postId)
            ->whereNull('post.deleted_at')
            ->whereNull('blog.deleted_at')
            ->first(['blog.id as blog_id', 'blog.slug as blog_slug', 'post.slug as post_slug']);

        return $row === null ? null : new PostLocation(
            (int) $row->blog_id,
            (string) $row->blog_slug,
            (string) $row->post_slug,
        );
    }
}
