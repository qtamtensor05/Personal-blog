<?php

declare(strict_types=1);

namespace App\Application\Blogs\Exceptions;

use DomainException;

final class BlogSlugTaken extends DomainException
{
    public function __construct()
    {
        parent::__construct('This slug is already in use by another blog.');
    }
}
