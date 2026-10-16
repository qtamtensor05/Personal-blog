<?php

namespace App\Modules\Content\Types;

use App\Models\User;
use Illuminate\Support\Facades\DB;

final class PageContentType implements ContentType
{
    public function table(): string
    {
        return 'pages';
    }

    public function singular(): string
    {
        return 'page';
    }

    public function draftFields(): array
    {
        return [
            'title',
            'content_json',
            'seo_title',
            'seo_description',
            'og_media_id',
            'canonical_url',
            'robots',
        ];
    }

    public function supportsTerms(): bool
    {
        return false;
    }

    public function creationAttributes(User $actor, array $data): array
    {
        return ['created_by' => $actor->id, 'page_key' => $data['slug']];
    }

    public function assertCanCreate(int $blogId): void {}

    public function assertCanArchive(int $blogId, object $row): void
    {
        $design = DB::table('blog_designs')->where('blog_id', $blogId)->first();

        foreach (['published_data', 'draft_data'] as $key) {
            $pageKeys = data_get(json_decode($design?->$key ?? '{}', true), 'navigation.pageKeys', []);

            abort_if(
                in_array($row->page_key, $pageKeys, true),
                422,
                'Gỡ trang khỏi menu nháp và live trước khi lưu trữ.',
            );
        }
    }
}
