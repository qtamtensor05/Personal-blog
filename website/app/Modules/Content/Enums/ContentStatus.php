<?php

namespace App\Modules\Content\Enums;

enum ContentStatus: string
{
    case Draft = 'DRAFT';
    case Scheduled = 'SCHEDULED';
    case Published = 'PUBLISHED';
    case Archived = 'ARCHIVED';
}
