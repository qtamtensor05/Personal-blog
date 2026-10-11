<?php

namespace App\Modules\Media\Actions;

use App\Models\User;
use App\Modules\Blogs\OwnerContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

final class UpdateMediaAction
{
    public function execute(User $actor, int $blog, int $id, array $input): void
    {
        app(OwnerContext::class)->resolve($actor, $blog, true);
        $data = Validator::make($input, ['alt_text' => 'nullable|string|max:500'])->validate();
        abort_unless(DB::table('media_assets')->where(['id' => $id, 'blog_id' => $blog, 'scope' => 'BLOG', 'status' => 'READY'])->whereNull('deleted_at')->update($data + ['updated_at' => now()]), 404);
    }
}
