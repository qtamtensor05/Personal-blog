<?php

declare(strict_types=1);

namespace App\Application\Media\Exceptions;

use DomainException;

final class MediaQuotaExceeded extends DomainException
{
    public function __construct()
    {
        parent::__construct('This blog has reached its storage quota.');
    }
}
