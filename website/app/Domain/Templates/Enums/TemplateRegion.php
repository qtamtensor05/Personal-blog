<?php

declare(strict_types=1);

namespace App\Domain\Templates\Enums;

/** Fixed page areas. A template may reorder slots only inside its own area. */
enum TemplateRegion: string
{
    case Header = 'header';
    case Body = 'body';
    case Sidebar = 'sidebar';
    case Footer = 'footer';
}
