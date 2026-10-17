<?php

declare(strict_types=1);

namespace App\Application\Content\Contracts;

use App\Application\Content\DTOs\PublicPostPage;

interface PublicPostCatalog
{
    public function paginate(?string $search, int $page, int $perPage): PublicPostPage;
}
