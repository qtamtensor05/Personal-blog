<?php

declare(strict_types=1);

use App\Presentation\Formatting\CompactCount;

it('abbreviates likes without rounding across the next threshold', function (): void {
    expect(CompactCount::format(0))->toBe('0')
        ->and(CompactCount::format(999))->toBe('999')
        ->and(CompactCount::format(1000))->toBe('1k')
        ->and(CompactCount::format(1500))->toBe('1.5k')
        ->and(CompactCount::format(9999))->toBe('9.9k')
        ->and(CompactCount::format(10_000))->toBe('10k')
        ->and(CompactCount::format(12_500))->toBe('12.5k')
        ->and(CompactCount::format(1_000_000))->toBe('1M');
});
