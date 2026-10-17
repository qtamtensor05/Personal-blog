<?php

declare(strict_types=1);

namespace App\Application\PlatformSettings\Exceptions;

use DomainException;

final class StalePlatformSettings extends DomainException
{
    public function __construct()
    {
        parent::__construct('Platform settings were changed by another administrator. Reload and try again.');
    }
}
