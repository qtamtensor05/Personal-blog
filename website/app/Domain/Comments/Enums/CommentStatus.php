<?php

declare(strict_types=1);

namespace App\Domain\Comments\Enums;

enum CommentStatus: string
{
    case Visible = 'VISIBLE';
    case OwnerHidden = 'OWNER_HIDDEN';
    case AdminHidden = 'ADMIN_HIDDEN';
}
