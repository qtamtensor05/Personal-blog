<?php

declare(strict_types=1);

namespace App\Presentation\Web\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateTemplateRequest extends FormRequest
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
            'layout' => ['nullable', 'string', 'in:default,lifestyle-editorial'],
            'preview_media_id' => ['nullable', 'integer', 'exists:media_assets,id'],
            'demo_url' => ['nullable', 'url', 'max:2048'],
            'draft_data' => ['nullable', 'array'],
            'draft_data.sections' => ['required_with:draft_data', 'array', 'min:1'],
            'draft_data.sections.*.key' => ['required_with:draft_data', 'string', 'max:80'],
            'draft_data.sections.*.section' => ['required_with:draft_data', 'string', 'max:80'],
            'draft_data.sections.*.props' => ['nullable', 'array'],
            'draft_data.designTokens' => ['required_with:draft_data', 'array'],
            'draft_data.designTokens.primaryColor' => [
                'required_with:draft_data',
                'regex:/^#[0-9A-Fa-f]{6}$/',
            ],
            'draft_data.designTokens.secondaryColor' => [
                'required_with:draft_data',
                'regex:/^#[0-9A-Fa-f]{6}$/',
            ],
            'draft_data.designTokens.accentColor' => [
                'required_with:draft_data',
                'regex:/^#[0-9A-Fa-f]{6}$/',
            ],
            'draft_data.designTokens.textColor' => [
                'required_with:draft_data',
                'regex:/^#[0-9A-Fa-f]{6}$/',
            ],
            'draft_data.designTokens.backgroundColor' => [
                'required_with:draft_data',
                'regex:/^#[0-9A-Fa-f]{6}$/',
            ],
            'draft_data.designTokens.headingFont' => [
                'required_with:draft_data',
                'string',
                'max:120',
            ],
            'draft_data.designTokens.bodyFont' => ['required_with:draft_data', 'string', 'max:120'],
            'sections' => ['nullable', 'array', 'min:1'],
            'sections.*' => [
                'string',
                'in:header,hero,image_text,gallery,recent_posts,categories,cta,profile,sidebar_posts,newsletter,footer',
            ],
            'slot_visibility' => ['nullable', 'array'],
            'slot_visibility.*' => ['nullable', 'boolean'],
            'slot_order' => ['nullable', 'array'],
            'slot_order.*' => ['nullable', 'integer', 'min:1', 'max:99'],
            'slot_props' => ['nullable', 'array'],
            'slot_props.*' => ['array'],
            'slot_props.*.*' => ['nullable'],
        ];
    }
}
