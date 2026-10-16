<?php

declare(strict_types=1);

namespace App\Domain\Moderation\Enums;

enum ReportStatus: string
{
    case Open = 'OPEN';
    case Reviewing = 'REVIEWING';
    case Resolved = 'RESOLVED';
    case Dismissed = 'DISMISSED';

    public function isPending(): bool
    {
        return $this === self::Open || $this === self::Reviewing;
    }
}
