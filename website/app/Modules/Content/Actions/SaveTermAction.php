<?php

namespace App\Modules\Content\Actions;

use App\Models\User;
use App\Modules\Blogs\OwnerContext;
use App\Modules\Content\Support\PostTermSynchronizer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

final class SaveTermAction
{
    public function __construct(
        private readonly OwnerContext $owners,
    ) {}

    public function execute(User $actor, int $blog, string $kind, array $input): void
    {
        abort_unless(in_array($kind, ['categories', 'tags']), 404);
        $this->owners->resolve($actor, $blog, true);
        $data = Validator::make($input, PostTermSynchronizer::INPUT_RULES)->validate();
        DB::transaction(function () use ($actor, $blog, $kind, $data): void {
            DB::table('blogs')->where('id', $blog)->lockForUpdate()->first();
            $this->owners->resolve($actor, $blog, true);
            if (DB::table($kind)->where(['blog_id' => $blog, 'slug' => $data['slug']])->exists()) {
                throw ValidationException::withMessages(['slug' => 'Đường dẫn đã tồn tại.']);
            }
            DB::table($kind)->insert($data + ['blog_id' => $blog, 'created_at' => now(), 'updated_at' => now()]);
        });
    }
}
