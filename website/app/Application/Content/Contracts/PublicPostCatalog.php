<?php

declare(strict_types=1);

namespace App\Application\Content\Contracts;

use App\Application\Content\DTOs\PublicPostPage;

interface PublicPostCatalog
{
    public function paginate(?string $search, int $page, int $perPage): PublicPostPage;

    public function featured(int $limit): array;

    public function recent(int $limit, array $excludeIds = []): array;
}
