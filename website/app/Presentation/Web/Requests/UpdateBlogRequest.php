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
            'status' => ['required', 'in:DRAFT,PUBLISHED'],
            'visibility' => ['nullable', 'in:PUBLIC,UNLISTED,PRIVATE'],
            'discoverable' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => __('Enter a blog name so readers can recognize your blog.'),
            'name.max' => __('The blog name may not exceed 160 characters. Shorten it and try again.'),
            'slug.regex' => __('The slug may only contain lowercase unaccented letters, numbers and hyphens; for example: my-blog.'),
            'slug.max' => __('The slug may not exceed 160 characters. Shorten it and try again.'),
            'status.in' => __('The status must be either draft or published.'),
            'visibility.in' => __('The visibility setting is invalid. Reload the page and select it again.'),
        ];
    }
}
