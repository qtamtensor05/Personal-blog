<?php

declare(strict_types=1);

namespace App\Infrastructure\Media;

use App\Application\Media\Contracts\MediaStorage;
use App\Application\Media\DTOs\StoredMedia;
use App\Application\Media\Exceptions\MediaStorageFailed;
use Illuminate\Support\Facades\Storage;

final readonly class LocalMediaStorage implements MediaStorage
{
    public function __construct(private string $disk = 'public') {}

    public function upload(string $localPath, string $folder, string $publicId): StoredMedia
    {
        $contents = file_get_contents($localPath);

        if ($contents === false) {
            throw new MediaStorageFailed('Uploaded file could not be read.');
        }

        $image = @getimagesize($localPath);
        $extension = $this->extension($image['mime'] ?? null);
        $path = trim($folder, '/').'/'.$publicId.'.'.$extension;

        if (! Storage::disk($this->disk)->put($path, $contents)) {
            throw new MediaStorageFailed('The image could not be written to local storage.');
        }

        return new StoredMedia(
            assetId: hash('sha256', $path),
            publicId: $path,
            version: time(),
            resourceType: 'image',
            deliveryType: 'upload',
            secureUrl: Storage::disk($this->disk)->url($path),
            format: $extension,
            width: isset($image[0]) ? (int) $image[0] : null,
            height: isset($image[1]) ? (int) $image[1] : null,
            bytes: strlen($contents),
        );
    }

    public function destroy(string $publicId): void
    {
        if (Storage::disk($this->disk)->exists($publicId) && ! Storage::disk($this->disk)->delete($publicId)) {
            throw new MediaStorageFailed('The local image could not be deleted.');
        }
    }

    public function cloudName(): string
    {
        return 'local-'.$this->disk;
    }

    private function extension(?string $mimeType): string
    {
        return match ($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            default => throw new MediaStorageFailed('The uploaded image format is not supported.'),
        };
    }
}
