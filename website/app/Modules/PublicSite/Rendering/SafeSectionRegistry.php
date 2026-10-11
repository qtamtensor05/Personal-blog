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
        $walk = function (mixed $node, int $depth = 0) use (&$walk): void {
            if ($depth > 30) {
                throw new InvalidArgumentException('Giao diện lồng quá sâu.');
            }
            if (! is_array($node)) {
                return;
            }
            foreach ($node as $key => $value) {
                if (in_array((string) $key, ['script', 'script-export', 'script-props'], true) || preg_match('/^on[a-z]+$/i', (string) $key)) {
                    throw new InvalidArgumentException('Không cho phép script hoặc event handler trong giao diện.');
                }
                if ($key === 'type' && is_string($value) && ! in_array($value, array_merge(self::TYPES, ['wrapper', 'textnode']), true)) {
                    throw new InvalidArgumentException('Component không được hỗ trợ.');
                }
                if (in_array((string) $key, ['src', 'href'], true) && is_string($value) && ! preg_match('~^(https://|/(?!/)|#)~', $value)) {
                    throw new InvalidArgumentException('URL giao diện không an toàn.');
                }
                if (is_string($value) && preg_match('/(?:javascript:|<script|\son[a-z]+\s*=)/i', $value)) {
                    throw new InvalidArgumentException('Dữ liệu giao diện không an toàn.');
                }
                if (is_array($value)) {
                    $walk($value, $depth + 1);
                }
            }
        };
        $walk($design['project']);
    }
}
