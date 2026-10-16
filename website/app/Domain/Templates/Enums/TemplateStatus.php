<?php

declare(strict_types=1);

namespace App\Domain\Templates\Enums;

enum TemplateStatus: string
{
    case Draft = 'DRAFT';
    case Published = 'PUBLISHED';
    case Hidden = 'HIDDEN';
    case Retired = 'RETIRED';
}
