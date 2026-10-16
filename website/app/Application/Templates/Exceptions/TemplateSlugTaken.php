<?php

declare(strict_types=1);

namespace App\Application\Templates\Exceptions;

use DomainException;

final class TemplateSlugTaken extends DomainException
{
    public function __construct()
    {
        parent::__construct('This slug is already in use by another template.');
    }
}
