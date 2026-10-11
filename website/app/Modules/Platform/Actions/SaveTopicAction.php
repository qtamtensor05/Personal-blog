<?php

namespace App\Modules\Platform\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

final class SaveTopicAction
{
    public function execute(User $actor, array $input): void
    {
        abort_unless($actor->canAccessApplication() && $actor->hasPlatformPermission('platform.topics.manage'), 403);
        $data = Validator::make($input, ['name' => 'required|string|max:160', 'slug' => 'required|alpha_dash|max:160|unique:blog_topics,slug'])->validate();
        DB::table('blog_topics')->insert($data + ['created_by' => $actor->id, 'created_at' => now(), 'updated_at' => now()]);
    }
}
