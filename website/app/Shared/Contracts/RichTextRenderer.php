<?php

namespace App\Shared\Contracts;

interface RichTextRenderer
{
    public function render(array $document, ?int $blogId = null): string;
}
