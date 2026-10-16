<?php

declare(strict_types=1);

namespace App\Domain\Blogs\Enums;

enum BlogVisibility: string
{
    case Public = 'PUBLIC';
    case Unlisted = 'UNLISTED';
    case Private = 'PRIVATE';
}
