<?php

declare(strict_types=1);

namespace App\Infrastructure\Templates\Persistence;

use App\Domain\Templates\ValueObjects\TemplateData;
use App\Domain\Templates\ValueObjects\TemplateDesignTokens;
use JsonException;
use UnexpectedValueException;

final class TemplateDataHydrator
{
    /**
     * @throws JsonException
     */
    public function fromJson(string $json): TemplateData
    {
        $data = json_decode($json, true, flags: JSON_THROW_ON_ERROR);

        if (! is_array($data)) {
            throw new UnexpectedValueException('Template data must be a JSON object.');
        }

        if (isset($data['sections'], $data['designTokens'])) {
            return TemplateData::fromArray($data);
        }

        if (isset($data['project']) && array_key_exists('schemaVersion', $data)) {
            return TemplateData::fromArray($this->convertLegacyData($data));
        }

        throw new UnexpectedValueException('Template data uses an unsupported schema.');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function convertLegacyData(array $data): array
    {
        $legacySections = $this->legacyHomepageSections($data);
        $sections = [];
        $usedKeys = [];

        foreach ($legacySections as $index => $section) {
            if (! is_array($section)) {
                continue;
            }

            $type = $this->mapLegacySection((string) ($section['type'] ?? ''));

            if ($type === null) {
                continue;
            }

            $baseKey = trim((string) ($section['id'] ?? "legacy-{$index}")) ?: "legacy-{$index}";
            $key = $baseKey;
            $suffix = 2;

            while (isset($usedKeys[$key])) {
                $key = $baseKey.'-'.$suffix++;
            }

            $usedKeys[$key] = true;
            $props = $section;
            unset($props['id'], $props['type']);
            $sections[] = ['key' => $key, 'section' => $type, 'props' => $props];
        }

        if ($sections === []) {
            $sections = [
                ['key' => 'hero', 'section' => 'hero', 'props' => []],
                ['key' => 'recent-posts', 'section' => 'recent_posts', 'props' => []],
                ['key' => 'footer', 'section' => 'footer', 'props' => []],
            ];
        }

        $tokens = TemplateDesignTokens::default()->jsonSerialize();
        $legacyTheme = is_array($data['themeDefaults'] ?? null) ? $data['themeDefaults'] : [];
        $colorMap = [
            'primary' => 'primaryColor',
            'secondary' => 'secondaryColor',
            'accent' => 'accentColor',
            'text' => 'textColor',
            'background' => 'backgroundColor',
        ];

        foreach ($colorMap as $legacyKey => $tokenKey) {
            $value = $legacyTheme[$legacyKey] ?? null;

            if (is_string($value) && preg_match('/^#[0-9A-Fa-f]{6}$/', $value)) {
                $tokens[$tokenKey] = $value;
            }
        }

        return [
            'sections' => $sections,
            'designTokens' => $tokens,
            'postLayout' => [],
        ];
    }

    /** @param array<string, mixed> $data
     * @return list<mixed>
     */
    private function legacyHomepageSections(array $data): array
    {
        $pages = $data['project']['pages'] ?? [];

        if (! is_array($pages)) {
            return [];
        }

        $homepage = (string) ($data['navigation']['homepage'] ?? 'home');

        foreach ($pages as $page) {
            if (
                is_array($page) &&
                ($page['id'] ?? null) === $homepage &&
                is_array($page['sections'] ?? null)
            ) {
                return array_values($page['sections']);
            }
        }

        return [];
    }

    private function mapLegacySection(string $type): ?string
    {
        return match ($type) {
            'header', 'hero', 'image' => 'hero',
            'text' => 'about',
            'post-list' => 'recent_posts',
            'footer' => 'footer',
            default => null,
        };
    }
}
