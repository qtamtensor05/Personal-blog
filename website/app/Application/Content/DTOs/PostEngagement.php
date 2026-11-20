<?php

declare(strict_types=1);

namespace App\Application\Content\DTOs;

final readonly class PostEngagement
{
    public function __construct(public int $likes, public int $views) {}
}
