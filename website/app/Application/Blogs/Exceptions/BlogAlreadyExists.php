<?php

declare(strict_types=1);

namespace App\Application\Blogs\Exceptions;

use DomainException;

final class BlogAlreadyExists extends DomainException
{
    public function __construct(public readonly int $blogId)
    {
        parent::__construct('The user already owns a blog.');
    }
}
