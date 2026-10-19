<?php

declare(strict_types=1);

namespace App\Presentation\Web\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:160'],
            'avatar' => [
                'nullable',
                Rule::prohibitedIf(fn (): bool => $this->boolean('remove_avatar')),
                'file',
                'image',
                'mimes:'.implode(',', config('media.allowed_mimes')),
                'max:'.config('media.max_upload_kb'),
            ],
            'remove_avatar' => ['required', 'boolean'],
            'current_password' => ['nullable', 'string', 'required_with:password'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ];
    }
}
