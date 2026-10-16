<?php

declare(strict_types=1);

namespace App\Application\Media\Exceptions;

use DomainException;

final class MediaInUse extends DomainException
{
    public function __construct()
    {
        parent::__construct('This media is still in use and cannot be deleted.');
    }
}
