<?php

namespace App\Modules\Design\Queries;

use App\Models\User;
use App\Modules\Blogs\OwnerContext;
use Illuminate\Support\Facades\DB;

final class DesignWorkspace
{
    public function __construct(
        private readonly OwnerContext $owners,
    ) {}

    public function find(User $actor, int $blog): ?object
    {
        $this->owners->resolve($actor, $blog);

        return DB::table('blog_designs')->where('blog_id', $blog)->first();
    }
}
