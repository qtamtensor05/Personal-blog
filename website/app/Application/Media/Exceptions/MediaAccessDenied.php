<?php

declare(strict_types=1);

namespace App\Application\Media\Exceptions;

use DomainException;

final class MediaAccessDenied extends DomainException
{
    public function __construct()
    {
        parent::__construct('You are not allowed to manage this media.');
    }
}
