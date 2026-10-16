<?php

declare(strict_types=1);

namespace App\Domain\Media\Enums;

enum MediaScope: string
{
    // Shared platform assets, e.g. template preview images.
    case Platform = 'PLATFORM';
    // Belongs to one blog: logo, covers, images inside posts.
    case Blog = 'BLOG';
    // Belongs to a user, e.g. avatar.
    case User = 'USER';
}
