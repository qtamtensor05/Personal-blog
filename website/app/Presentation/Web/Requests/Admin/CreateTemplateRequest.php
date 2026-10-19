<?php

declare(strict_types=1);

namespace App\Presentation\Web\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

final class CreateTemplateRequest extends FormRequest
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
            'preview_media_id' => ['nullable', 'integer', 'exists:media_assets,id'],
            'demo_url' => ['nullable', 'url', 'max:2048'],
            'draft_data' => ['required', 'array'],
            'draft_data.sections' => ['required', 'array', 'min:1'],
            'draft_data.sections.*.key' => ['required', 'string', 'max:80'],
            'draft_data.sections.*.section' => ['required', 'string', 'max:80'],
            'draft_data.sections.*.props' => ['nullable', 'array'],
            'draft_data.designTokens' => ['required', 'array'],
            'draft_data.designTokens.primaryColor' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'draft_data.designTokens.secondaryColor' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'draft_data.designTokens.accentColor' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'draft_data.designTokens.textColor' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'draft_data.designTokens.backgroundColor' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'draft_data.designTokens.headingFont' => ['required', 'string', 'max:120'],
            'draft_data.designTokens.bodyFont' => ['required', 'string', 'max:120'],
        ];
    }
}
