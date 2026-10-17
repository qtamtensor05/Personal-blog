<?php

declare(strict_types=1);

namespace App\Presentation\Web\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdatePlatformSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'revision' => ['required', 'integer', 'min:0'],
            'site_name' => ['required', 'string', 'max:160'],
            'registration_enabled' => ['required', 'boolean'],
            'default_locale' => ['required', Rule::in(['vi', 'en'])],
            'default_timezone' => ['required', 'timezone:all'],
            'default_blog_post_limit' => ['required', 'integer', 'between:1,10000'],
            'default_media_quota_mb' => ['required', 'integer', 'between:1,10240'],
            'maintenance_banner_enabled' => ['required', 'boolean'],
            'maintenance_banner_message' => ['nullable', 'string', 'max:500', 'required_if:maintenance_banner_enabled,1'],
        ];
    }
}
