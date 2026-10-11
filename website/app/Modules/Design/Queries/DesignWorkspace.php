<?php

namespace App\Modules\Design\Queries;

use App\Models\User;
use App\Modules\Blogs\OwnerContext;
use Illuminate\Support\Facades\DB;

final class DesignWorkspace
{
    public function find(User $actor, int $blog): ?object
    {
        app(OwnerContext::class)->resolve($actor, $blog);

        return DB::table('blog_designs')->where('blog_id', $blog)->first();
    }
}
