<?php

declare(strict_types=1);

namespace App\Application\Content\Contracts;

use App\Application\Content\DTOs\PostLocation;

interface PostLocationRepository
{
    public function findForPost(int $postId): ?PostLocation;
}
