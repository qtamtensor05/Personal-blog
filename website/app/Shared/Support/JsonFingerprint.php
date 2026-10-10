<?php

namespace App\Shared\Support;

final class JsonFingerprint
{
    public static function make(array $payload): string
    {
        return hash(
            'sha256',
            json_encode(self::sort($payload), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
        );
    }

    private static function sort(array $value): array
    {
        if (array_is_list($value)) {
            return array_map(
                fn (mixed $item): mixed => is_array($item) ? self::sort($item) : $item,
                $value,
            );
        }

        ksort($value);

        return array_map(
            fn (mixed $item): mixed => is_array($item) ? self::sort($item) : $item,
            $value,
        );
    }
}
