<?php

declare(strict_types=1);

namespace App\Application\Media\UseCases\DeleteMedia;

interface DeleteMediaUseCase
{
    public function execute(int $actorId, int $mediaId): void;
}
