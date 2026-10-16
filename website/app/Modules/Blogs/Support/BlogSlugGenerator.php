<?php

namespace App\Modules\Blogs\Support;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class BlogSlugGenerator
{
    private const MAX_LENGTH = 160;

    /**
     * Normalise the requested slug, falling back to the blog name.
     *
     * @param  array<string, mixed>  $input
     */
    public function fromInput(array $input): string
    {
        $raw = isset($input['slug']) && trim((string) $input['slug']) !== ''
            ? (string) $input['slug']
            : (string) ($input['name'] ?? '');

        $slug = Str::slug($raw);

        if (Str::length($slug) > self::MAX_LENGTH) {
            throw ValidationException::withMessages([
                'slug' => 'Đường dẫn sau chuẩn hóa không được vượt quá 160 ký tự.',
            ]);
        }

        if ($slug === '') {
            throw ValidationException::withMessages([
                'slug' => 'Không thể sinh đường dẫn hợp lệ từ tên đã nhập. Hãy nhập đường dẫn thủ công.',
            ]);
        }

        return $slug;
    }
}
