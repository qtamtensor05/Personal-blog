<?php

declare(strict_types=1);

namespace App\Domain\Content\Enums;

enum PostStatus: string
{
    case Draft = 'DRAFT';
    // Exists in the schema; scheduling needs a scheduler job and is not part of the MVP.
    case Scheduled = 'SCHEDULED';
    case Published = 'PUBLISHED';
    case Archived = 'ARCHIVED';
}
