<?php

declare(strict_types=1);

namespace App\Presentation\Formatting;

final class CompactCount
{
    public static function format(int $count): string
    {
        if ($count < 1000) {
            return (string) $count;
        }

        $divisor = $count >= 1_000_000 ? 1_000_000 : 1000;
        $suffix = $divisor === 1_000_000 ? 'M' : 'k';
        $whole = intdiv($count, $divisor);
        $decimal = intdiv($count % $divisor, intdiv($divisor, 10));

        return $whole.($decimal > 0 ? '.'.$decimal : '').$suffix;
    }
}
