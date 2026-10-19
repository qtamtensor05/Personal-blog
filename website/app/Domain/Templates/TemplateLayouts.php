<?php

declare(strict_types=1);

namespace App\Domain\Templates;

use InvalidArgumentException;

final class TemplateLayouts
{
    public const DEFAULT = 'default';

    public const LIFESTYLE_EDITORIAL = 'lifestyle-editorial';

    /** @return list<string> */
    public static function all(): array
    {
        return [self::DEFAULT, self::LIFESTYLE_EDITORIAL];
    }

    public static function ensureValid(string $layout): string
    {
        if (! in_array($layout, self::all(), true)) {
            throw new InvalidArgumentException("Unknown template layout: {$layout}");
        }

        return $layout;
    }
}
