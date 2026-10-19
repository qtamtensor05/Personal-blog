<?php

declare(strict_types=1);

namespace App\Presentation\Web\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateBlogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:160'],
            'slug' => ['nullable', 'string', 'max:160', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'description' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', 'in:DRAFT,PUBLISHED,HIDDEN,ARCHIVED'],
            'visibility' => ['required', 'in:PUBLIC,UNLISTED,PRIVATE'],
            'discoverable' => ['required', 'boolean'],
        ];
    }
}
