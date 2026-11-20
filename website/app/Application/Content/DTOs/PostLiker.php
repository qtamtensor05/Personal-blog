<?php

declare(strict_types=1);

namespace App\Application\Content\DTOs;

final readonly class PostLiker
{
    public function __construct(public int $userId, public string $name, public ?string $avatarUrl, public string $likedAt) {}
}
