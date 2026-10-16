<?php

declare(strict_types=1);

namespace App\Presentation\Web\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateBlogSettingsRequest extends FormRequest
{
    /** Platforms shown in the settings form. */
    public const SOCIAL_PLATFORMS = ['website', 'github', 'twitter', 'facebook', 'linkedin', 'instagram', 'youtube'];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'locale' => ['required', 'string', 'in:vi,en'],
            'timezone' => ['required', 'string', 'max:80', 'timezone:all'],
            'posts_per_page' => ['required', 'integer', 'between:1,100'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:500'],
            'logo_media_id' => ['nullable', 'integer', 'min:1'],
            'favicon_media_id' => ['nullable', 'integer', 'min:1'],
            'og_media_id' => ['nullable', 'integer', 'min:1'],
            'social_links' => ['nullable', 'array'],
        ];

        foreach (self::SOCIAL_PLATFORMS as $platform) {
            // https only: these URLs are rendered as links on the public blog.
            $rules["social_links.{$platform}"] = ['nullable', 'url:https', 'max:2048'];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function socialLinks(): array
    {
        $links = (array) $this->validated('social_links', []);

        return array_filter(
            array_intersect_key($links, array_flip(self::SOCIAL_PLATFORMS)),
            fn ($url) => is_string($url) && $url !== '',
        );
    }

    public function mediaId(string $key): ?int
    {
        $value = $this->validated($key);

        return $value === null ? null : (int) $value;
    }
}
