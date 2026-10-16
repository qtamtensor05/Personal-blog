<?php

declare(strict_types=1);

namespace App\Infrastructure\Media\Persistence;

use App\Application\Media\Contracts\BlogOwnership;
use Illuminate\Support\Facades\DB;

final class QueryBuilderBlogOwnership implements BlogOwnership
{
    public function ownerOf(int $blogId): ?int
    {
        $owner = DB::table('blogs')
            ->where('id', $blogId)
            ->whereNull('deleted_at')
            ->value('owner_user_id');

        return $owner === null ? null : (int) $owner;
    }
}
