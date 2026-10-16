<?php

declare(strict_types=1);

namespace App\Application\Blogs\Exceptions;

use DomainException;

final class BlogNotFound extends DomainException
{
    public function __construct()
    {
        parent::__construct('Blog not found.');
    }
}
