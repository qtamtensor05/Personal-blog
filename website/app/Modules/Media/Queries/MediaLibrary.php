<?php

namespace App\Modules\Media\Queries;

use App\Models\User;
use App\Modules\Blogs\OwnerContext;
use Illuminate\Support\Facades\DB;

final class MediaLibrary
{
    public function list(User $actor, int $blog, string $q = '')
    {
        app(OwnerContext::class)->resolve($actor, $blog);

        return DB::table('media_assets')->where(['blog_id' => $blog, 'scope' => 'BLOG'])->whereNull('deleted_at')->when($q !== '', fn ($query) => $query->where('original_name', 'like', '%'.$q.'%'))->latest()->get();
    }
}
