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

    public function upload(string $localPath, string $folder, string $publicId, ?string $mimeType = null): StoredMedia
    {
        $contents = file_get_contents($localPath);

        if ($contents === false) {
            throw new MediaStorageFailed('Uploaded file could not be read.');
        }

        $isVideo = str_starts_with((string) $mimeType, 'video/');
        $image = $isVideo ? false : @getimagesize($localPath);
        $extension = $this->extension($mimeType ?? ($image['mime'] ?? null));
        $path = trim($folder, '/').'/'.$publicId.'.'.$extension;

        if (! Storage::disk($this->disk)->put($path, $contents)) {
            throw new MediaStorageFailed('The media could not be written to local storage.');
        }

        return new StoredMedia(
            assetId: hash('sha256', $path),
            publicId: $path,
            version: time(),
            resourceType: $isVideo ? 'video' : 'image',
            deliveryType: 'upload',
            secureUrl: Storage::disk($this->disk)->url($path),
            format: $extension,
            width: isset($image[0]) ? (int) $image[0] : null,
            height: isset($image[1]) ? (int) $image[1] : null,
            bytes: strlen($contents),
        );
    }

    public function destroy(string $publicId, string $resourceType = 'image'): void
    {
        if (Storage::disk($this->disk)->exists($publicId) && ! Storage::disk($this->disk)->delete($publicId)) {
            throw new MediaStorageFailed('The local media could not be deleted.');
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
            'video/mp4' => 'mp4',
            'video/webm' => 'webm',
            'video/quicktime' => 'mov',
            default => throw new MediaStorageFailed('The uploaded media format is not supported.'),
        };
    }
}
