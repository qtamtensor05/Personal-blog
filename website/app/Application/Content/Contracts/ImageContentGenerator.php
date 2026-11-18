<?php

declare(strict_types=1);

namespace App\Application\Content\Contracts;

use App\Application\Content\DTOs\ImageWritingInput;
use App\Application\Content\DTOs\PostWritingSuggestion;

interface ImageContentGenerator
{
    /** Returns editable post fields without persisting or publishing them. */
    public function generate(ImageWritingInput $input): PostWritingSuggestion;
}
