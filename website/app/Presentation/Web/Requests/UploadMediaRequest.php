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
            'blog_id' => ['nullable', 'integer', 'min:1', 'required_if:scope,BLOG', 'prohibited_unless:scope,BLOG'],
            'alt_text' => ['nullable', 'string', 'max:500'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        $maxMb = (float) config('media.max_upload_kb') / 1024;

        return [
            'file.required' => 'Vui lòng chọn hình ảnh hoặc video để tải lên.',
            'file.uploaded' => 'Tệp tải lên không thành công. Tệp có thể vượt quá giới hạn của PHP; hãy chọn tệp tối đa '.$maxMb.' MB.',
            'file.file' => 'Tệp tải lên không hợp lệ.',
            'file.mimes' => 'Chỉ hỗ trợ ảnh JPG, PNG, WEBP, GIF và video MP4, WEBM, MOV.',
            'file.max' => 'Tệp không được lớn hơn '.$maxMb.' MB.',
        ];
    }
}
