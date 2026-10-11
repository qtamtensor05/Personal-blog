<?php

namespace App\Modules\Blogs;

use App\Models\User;
use Illuminate\Support\Facades\DB;

final class OwnerContext
{
    public function resolve(User $actor, int $blog, bool $write = false): object
    {
        abort_unless($actor->canAccessApplication(), 403);
        $record = DB::table('blogs')->where(['id' => $blog, 'owner_user_id' => $actor->id])->whereNull('deleted_at')->first();
        abort_unless($record, 404);
        abort_if($write && in_array($record->status, ['SUSPENDED', 'ARCHIVED']), 403, 'Blog đang bị hạn chế chỉnh sửa.');

        return $record;
    }

    public function list(User $actor)
    {
        return DB::table('blogs')->where('owner_user_id', $actor->id)->whereNull('deleted_at')->orderBy('name')->get();
    }
}
