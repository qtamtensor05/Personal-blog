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
            // `mimes` checks the detected file content, not just its extension.
            'file' => [
                'required',
                'file',
                'mimes:'.implode(',', config('media.allowed_mimes')),
                'max:'.config('media.max_upload_kb'),
            ],
            'scope' => ['required', Rule::enum(MediaScope::class)],
            'blog_id' => [
                'nullable',
                'integer',
                'min:1',
                'required_if:scope,BLOG',
                'prohibited_unless:scope,BLOG',
            ],
            'alt_text' => ['nullable', 'string', 'max:500'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        $maxMb = (float) config('media.max_upload_kb') / 1024;

        return [
            'file.required' => __('Please choose an image or video to upload.'),
            'file.uploaded' => __('The upload failed. The file may exceed the PHP upload limit; choose a file up to :max MB.', ['max' => $maxMb]),
            'file.file' => __('The uploaded file is invalid.'),
            'file.mimes' => __('Only JPG, PNG, WEBP, GIF images and MP4, WEBM, MOV videos are supported.'),
            'file.max' => __('The file may not exceed :max MB.', ['max' => $maxMb]),
        ];
    }
}
