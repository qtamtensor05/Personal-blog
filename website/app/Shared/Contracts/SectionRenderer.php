<?php

namespace App\Shared\Contracts;

interface SectionRenderer
{
    public function supports(string $type): bool;

    public function render(array $section, array $context): string;
}
