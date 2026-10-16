<?php

declare(strict_types=1);

namespace App\Application\Templates\Exceptions;

use DomainException;

final class TemplateNotFound extends DomainException
{
    public function __construct()
    {
        parent::__construct('Template not found.');
    }
}
