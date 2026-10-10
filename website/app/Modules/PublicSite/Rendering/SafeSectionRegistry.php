<?php

namespace App\Modules\PublicSite\Rendering;

use InvalidArgumentException;

final class SafeSectionRegistry
{
    private const TYPES = ['header', 'hero', 'text', 'image', 'post-list', 'footer'];

    public function validate(array $design): void
    {
        if (($design['schemaVersion'] ?? null) !== 1 || ! is_array($design['project'] ?? null)) {
            throw new InvalidArgumentException('Cấu hình giao diện không đúng phiên bản schema.');
        }

        foreach ($design['project']['pages'] ?? [] as $page) {
            foreach ($page['sections'] ?? [] as $section) {
                $type = $section['type'] ?? '';
                if (! in_array($type, self::TYPES, true)) {
                    throw new InvalidArgumentException("Component không được hỗ trợ: {$type}");
                }
                $encoded = json_encode($section, JSON_THROW_ON_ERROR);
                if (preg_match('/(?:javascript:|<script|\son[a-z]+\s*=)/i', $encoded)) {
                    throw new InvalidArgumentException('Component chứa nội dung không an toàn.');
                }
            }
        }
    }
}
