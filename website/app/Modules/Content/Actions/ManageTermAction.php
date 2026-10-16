<?php

namespace App\Modules\Content\Actions;

use App\Models\User;
use App\Modules\Blogs\OwnerContext;
use App\Modules\Content\Support\PostTermSynchronizer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

final class ManageTermAction
{
    public function __construct(
        private readonly OwnerContext $owners,
    ) {}

    public function update(
        User $actor,
        int $blogId,
        string $type,
        int $id,
        array $input
    ): void {
        $this->checkType($type);

        $data = Validator::make($input, PostTermSynchronizer::INPUT_RULES)->validate();

        DB::transaction(function () use (
            $actor, $blogId, $type, $id, $data
        ): void {
            DB::table('blogs')
                ->where('id', $blogId)
                ->lockForUpdate()
                ->first();

            $this->owners
                ->resolve($actor, $blogId, true);

            DB::table($type)
                ->where(['id' => $id, 'blog_id' => $blogId])
                ->lockForUpdate()
                ->firstOrFail();

            $exists = DB::table($type)
                ->where('blog_id', $blogId)
                ->where('slug', $data['slug'])
                ->where('id', '!=', $id)
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    'slug' => 'Đường dẫn đã tồn tại trong blog.',
                ]);
            }

            DB::table($type)
                ->where(['id' => $id, 'blog_id' => $blogId])
                ->update($data + ['updated_at' => now()]);
        });
    }

    public function delete(
        User $actor,
        int $blogId,
        string $type,
        int $id
    ): void {
        $this->checkType($type);

        DB::transaction(function () use (
            $actor, $blogId, $type, $id
        ): void {
            DB::table('blogs')
                ->where('id', $blogId)
                ->lockForUpdate()
                ->first();

            $this->owners
                ->resolve($actor, $blogId, true);

            DB::table($type)
                ->where(['id' => $id, 'blog_id' => $blogId])
                ->lockForUpdate()
                ->firstOrFail();

            ['join' => $join, 'column' => $column, 'draftKey' => $draftKey] = PostTermSynchronizer::TERMS[$type];

            if (DB::table($join)
                ->where(['blog_id' => $blogId, $column => $id])
                ->exists()) {
                throw ValidationException::withMessages([
                    'term' => 'Danh mục/thẻ đang được bài viết sử dụng.',
                ]);
            }

            if ($type === 'categories' &&
                DB::table('categories')
                    ->where([
                        'blog_id' => $blogId,
                        'parent_id' => $id,
                    ])->exists()) {
                throw ValidationException::withMessages([
                    'term' => 'Danh mục đang có danh mục con.',
                ]);
            }

            $posts = DB::table('posts')
                ->where('blog_id', $blogId)
                ->whereNull('deleted_at')
                ->whereNotNull('draft_data')
                ->cursor(['draft_data']);

            foreach ($posts as $post) {
                $draft = json_decode($post->draft_data, true) ?: [];

                foreach ($draft[$draftKey] ?? [] as $linkedId) {
                    if ((int) $linkedId === $id) {
                        throw ValidationException::withMessages([
                            'term' => 'Danh mục/thẻ còn nằm trong bản nháp hoặc lịch đăng.',
                        ]);
                    }
                }
            }

            DB::table($type)
                ->where(['id' => $id, 'blog_id' => $blogId])
                ->delete();
        });
    }

    private function checkType(string $type): void
    {
        abort_unless(
            in_array($type, ['categories', 'tags'], true),
            404
        );
    }
}
