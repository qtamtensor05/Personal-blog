<?php

declare(strict_types=1);

namespace App\Domain\Blogs\Enums;

enum BlogStatus: string
{
    case Draft = 'DRAFT';
    case Published = 'PUBLISHED';
    case Hidden = 'HIDDEN';
    case Suspended = 'SUSPENDED';
    case Archived = 'ARCHIVED';
}
