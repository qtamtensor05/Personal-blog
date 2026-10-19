<?php

declare(strict_types=1);

namespace App\Presentation\Web\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SaveSlotContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'region' => ['nullable', 'in:header,body,sidebar,footer'],
            'publish' => ['nullable', 'boolean'],
            'slot_content' => ['required', 'array'],
            'slot_content.*' => ['array'],
            'slot_content.*.title' => ['nullable', 'string', 'max:200'],
            'slot_content.*.eyebrow' => ['nullable', 'string', 'max:120'],
            'slot_content.*.description' => ['nullable', 'string', 'max:5000'],
            'slot_content.*.archiveTitle' => ['nullable', 'string', 'max:200'],
            'slot_content.*.archiveDescription' => ['nullable', 'string', 'max:1000'],
            'slot_content.*.copyright' => ['nullable', 'string', 'max:300'],
            'slot_content.*.ctaLabel' => ['nullable', 'string', 'max:120'],
            'slot_content.*.ctaUrl' => [
                'nullable',
                'string',
                'max:2048',
                'regex:/\A(?:https?:\/\/|mailto:|\/(?!\/)|#)[^\s]+\z/i',
            ],
            'slot_content.*.email' => ['nullable', 'email:rfc', 'max:255'],
            'slot_content.*.phone' => [
                'nullable',
                'string',
                'max:30',
                'regex:/\A[+0-9][0-9 .()\-]{5,29}\z/',
            ],
            'slot_content.*.facebookUrl' => ['nullable', 'url:http,https', 'max:2048'],
            'slot_content.*.youtubeUrl' => ['nullable', 'url:http,https', 'max:2048'],
            'slot_content.*.tiktokUrl' => ['nullable', 'url:http,https', 'max:2048'],
            'slot_content.*.zaloUrl' => ['nullable', 'url:http,https', 'max:2048'],
            'slot_content.*.ctaUrl' => [
                'nullable',
                'string',
                'max:2048',
                'regex:/\A(?:https?:\/\/|mailto:|\/(?!\/)|#)[^\s]+\z/i',
            ],
            'slot_content.*.mediaId' => ['nullable', 'integer', 'min:1'],
            'slot_content.*.mediaIds' => ['nullable', 'string', 'max:120'],
            'slot_content.*.categoryId' => ['nullable', 'integer', 'min:1'],
            'slot_content.*.categoryIds' => ['nullable', 'string', 'max:240'],
            'slot_content.*.limit' => ['nullable', 'integer', 'min:1', 'max:12'],
        ];
    }
}
