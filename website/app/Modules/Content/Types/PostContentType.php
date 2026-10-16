<?php

namespace App\Modules\Content\Types;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class PostContentType implements ContentType
{
    public function table(): string
    {
        return 'posts';
    }

    public function singular(): string
    {
        return 'post';
    }

    public function draftFields(): array
    {
        return [
            'title',
            'excerpt',
            'content_json',
            'seo_title',
            'seo_description',
            'cover_media_id',
            'og_media_id',
            'canonical_url',
            'robots',
        ];
    }

    public function supportsTerms(): bool
    {
        return true;
    }

    public function creationAttributes(User $actor, array $data): array
    {
        return ['author_user_id' => $actor->id];
    }

    public function assertCanCreate(int $blogId): void
    {
        $quota = DB::table('blog_settings')->where('blog_id', $blogId)->value('post_limit');

        if ($quota === null) {
            return;
        }

        $count = DB::table('posts')->where('blog_id', $blogId)->whereNull('deleted_at')->count();

        if ($count >= $quota) {
            throw ValidationException::withMessages(['quota' => 'Blog đã đạt giới hạn bài viết.']);
        }
    }

    public function assertCanArchive(int $blogId, object $row): void {}
}
