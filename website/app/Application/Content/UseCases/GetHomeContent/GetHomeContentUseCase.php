<?php

declare(strict_types=1);

namespace App\Application\Content\UseCases\GetHomeContent;

use App\Application\Content\DTOs\HomeContent;

interface GetHomeContentUseCase
{
    public function execute(): HomeContent;
}
