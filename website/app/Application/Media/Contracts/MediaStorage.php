<?php

declare(strict_types=1);

namespace App\Application\Media\Contracts;

use App\Application\Media\DTOs\StoredMedia;
use App\Application\Media\Exceptions\MediaStorageFailed;

interface MediaStorage
{
    /**
     * @throws MediaStorageFailed
     */
    public function upload(string $localPath, string $folder, string $publicId): StoredMedia;

    /**
     * Deleting an asset that no longer exists is treated as success.
     *
     * @throws MediaStorageFailed
     */
    public function destroy(string $publicId): void;

    public function cloudName(): string;
}
