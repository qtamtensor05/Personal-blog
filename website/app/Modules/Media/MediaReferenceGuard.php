<?php

namespace App\Modules\Media;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class MediaReferenceGuard
{
    public function assertReadyForBlog(
        int $blogId,
        array $content
    ): void {
        $ids = [];

        foreach (['cover_media_id', 'og_media_id'] as $field) {
            if (
                isset($content[$field]) &&
                $content[$field] !== ''
            ) {
                $ids[] = $this->validId($content[$field]);
            }
        }

        $document = $content['content_json'] ?? null;

        if (is_string($document)) {
            $document = json_decode($document, true);
        }

        if (
            ! is_array($document) ||
            ($document['type'] ?? null) !== 'doc'
        ) {
            throw ValidationException::withMessages([
                'content_json' => 'Nội dung Tiptap không hợp lệ.',
            ]);
        }

        $walk = function (array $node) use (
            &$walk,
            &$ids
        ): void {
            if (($node['type'] ?? null) === 'image') {
                $ids[] = $this->validId(
                    $node['attrs']['mediaId'] ?? null
                );
            }

            foreach ($node['content'] ?? [] as $child) {
                if (is_array($child)) {
                    $walk($child);
                }
            }
        };

        $walk($document);

        $ids = array_unique($ids);
        sort($ids, SORT_NUMERIC);

        foreach ($ids as $id) {
            $asset = DB::table('media_assets')
                ->where([
                    'id' => $id,
                    'blog_id' => $blogId,
                    'scope' => 'BLOG',
                    'status' => 'READY',
                ])
                ->whereNull('deleted_at')
                ->lockForUpdate()
                ->first();

            if (! $asset) {
                throw ValidationException::withMessages([
                    'media' => 'Ảnh không thuộc blog, đã bị xóa hoặc chưa sẵn sàng.',
                ]);
            }
        }
    }

    private function validId(mixed $value): int
    {
        if (
            ! is_int($value) &&
            ! (is_string($value) && ctype_digit($value))
        ) {
            throw ValidationException::withMessages([
                'media' => 'Ảnh phải được chọn từ thư viện media của blog.',
            ]);
        }

        $id = filter_var(
            $value,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        if ($id === false) {
            throw ValidationException::withMessages([
                'media' => 'Mã ảnh không hợp lệ.',
            ]);
        }

        return (int) $id;
    }
}
