<?php

namespace App\Modules\Design;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

final class DesignDocument
{
    public static function snapshot(object $template): array
    {
        $data = json_decode($template->template_data, true, 512, JSON_THROW_ON_ERROR);
        $data['sourceVersion'] = $template->version_number;
        $data['theme'] = $data['themeDefaults'] ?? ['accent' => '#147d73'];

        return $data;
    }

    public static function sections(array $data): array
    {
        return data_get($data, 'project.pages.0.sections', data_get($data, 'project.pages.0.frames.0.component.components', []));
    }

    public static function form(array $data): array
    {
        $hero = collect(self::sections($data))->firstWhere('type', 'hero') ?? [];

        return ['accent' => data_get($data, 'theme.accent', data_get($data, 'themeDefaults.accent', '#147d73')),
            'heading' => $hero['content'] ?? 'Một câu chuyện riêng', 'description' => $hero['description'] ?? '',
            'layout' => data_get($data, 'theme.layout', 'journal'),
            'footer' => data_get($data, 'navigation.footerText', 'Viết điều đáng nhớ.'), 'menu_page_keys' => data_get($data, 'navigation.pageKeys', [])];
    }

    public static function apply(array $data, array $input): array
    {
        $input = Validator::make($input, [
            'accent' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'heading' => ['required', 'string', 'max:160'], 'description' => ['nullable', 'string', 'max:1000'],
            'layout' => ['required', 'in:journal,journey,magazine'], 'footer' => ['nullable', 'string', 'max:300'],
            'project' => ['sometimes', 'array'],
        ])->validate();
        $allowed = data_get($data, 'customizationRules.fields', ['accent', 'heading', 'description', 'layout', 'footer']);
        $current = self::form($data);
        foreach ($input as $key => $value) {
            if (in_array($key, $allowed, true)) {
                $current[$key] = $value;
            }
        }
        $data['theme'] = array_merge($data['theme'] ?? [], ['accent' => $current['accent'], 'layout' => $current['layout']]);
        $data['themeDefaults'] = $data['theme'];
        $sections = self::sections($data);
        $found = false;
        foreach ($sections as &$section) {
            if (($section['type'] ?? '') === 'hero') {
                $section['content'] = $current['heading'];
                $section['description'] = $current['description'];
                $found = true;
            }
        }
        unset($section);
        if (! $found) {
            array_unshift($sections, ['id' => 'hero', 'type' => 'hero', 'content' => $current['heading'], 'description' => $current['description']]);
        }
        $data['project']['pages'][0]['sections'] = $sections;
        $data['navigation']['footerText'] = $current['footer'];
        if (isset($input['project']['pages'][0]['frames'][0]['component'])) {
            $data['project'] = $input['project'];
            $components = data_get($input, 'project.pages.0.frames.0.component.components', []);
            foreach ($components as &$component) {
                if (($component['type'] ?? '') === 'hero') {
                    $component['content'] = $current['heading'];
                    $component['description'] = $current['description'];
                }
            }
            unset($component);
            $data['project']['pages'][0]['frames'][0]['component']['components'] = $components;
            $data['project']['pages'][0]['sections'] = array_map(fn ($section) => array_intersect_key($section, array_flip(['id', 'type', 'content', 'description'])), $components);
        }

        return $data;
    }

    public static function preview(array $form): array
    {
        $base = ['schemaVersion' => 1, 'project' => ['pages' => [['id' => 'home', 'sections' => [['type' => 'post-list']]]]]];
        try {
            return self::apply($base, $form);
        } catch (ValidationException) {
            return $base;
        }
    }
}
