<?php

namespace App\Modules\Content\Actions;

use App\Models\User;
use App\Modules\Blogs\OwnerContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

final class SaveTermAction
{
    public function execute(User $actor, int $blog, string $kind, array $input): void
    {
        abort_unless(in_array($kind, ['categories', 'tags']), 404);
        app(OwnerContext::class)->resolve($actor, $blog, true);
        $data = Validator::make($input, ['name' => 'required|string|max:160', 'slug' => 'required|alpha_dash|max:160'])->validate();
        DB::transaction(function () use ($actor, $blog, $kind, $data): void {
            DB::table('blogs')->where('id', $blog)->lockForUpdate()->first();
            app(OwnerContext::class)->resolve($actor, $blog, true);
            if (DB::table($kind)->where(['blog_id' => $blog, 'slug' => $data['slug']])->exists()) {
                throw ValidationException::withMessages(['slug' => 'Đường dẫn đã tồn tại.']);
            }
            DB::table($kind)->insert($data + ['blog_id' => $blog, 'created_at' => now(), 'updated_at' => now()]);
        });
    }
}
