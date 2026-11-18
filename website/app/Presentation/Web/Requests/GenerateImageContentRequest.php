<?php

declare(strict_types=1);

namespace App\Presentation\Web\Requests;

use App\Application\Content\DTOs\PostWritingSuggestion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class GenerateImageContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fields' => ['sometimes', 'array', 'min:1', 'max:7'],
            'fields.*' => ['required', 'string', 'distinct', Rule::in(PostWritingSuggestion::FIELDS)],
            'analysis_image' => ['sometimes', 'file', 'mimes:jpg,jpeg,png,webp', 'max:1024', 'dimensions:max_width=1024,max_height=1024'],
            'media_id' => ['required', 'integer', 'min:1'],
            'title' => ['nullable', 'string', 'max:160'],
            'context' => ['nullable', 'string', 'max:4000'],
            'instructions' => ['nullable', 'string', 'max:1000'],
            'locale' => ['required', 'in:vi,en'],
        ];
    }
}
