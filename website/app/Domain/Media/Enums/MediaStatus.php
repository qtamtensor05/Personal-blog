<?php

declare(strict_types=1);

namespace App\Domain\Media\Enums;

enum MediaStatus: string
{
    case Uploading = 'UPLOADING';
    case Ready = 'READY';
    case Failed = 'FAILED';
    case Deleting = 'DELETING';
    case Deleted = 'DELETED';
}
