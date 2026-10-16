<?php

declare(strict_types=1);

namespace App\Presentation\Web\Requests;

use App\Domain\Media\Enums\MediaScope;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UploadMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // `image` + `mimes` check the real content type, not just the extension.
            'file' => [
                'required',
                'file',
                'image',
                'mimes:'.implode(',', config('media.allowed_mimes')),
                'max:'.config('media.max_upload_kb'),
            ],
            'scope' => ['required', Rule::enum(MediaScope::class)],
            'blog_id' => ['nullable', 'integer', 'min:1', 'required_if:scope,BLOG', 'prohibited_unless:scope,BLOG'],
            'alt_text' => ['nullable', 'string', 'max:500'],
        ];
    }
}
