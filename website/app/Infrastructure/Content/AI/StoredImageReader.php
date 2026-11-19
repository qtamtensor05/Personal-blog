<?php

declare(strict_types=1);

namespace App\Infrastructure\Content\AI;

use App\Application\Content\Exceptions\ImageWritingUnavailable;
use App\Application\Media\DTOs\MediaAssetRecord;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Throwable;

final class StoredImageReader
{
    private const MAX_BYTES = 5 * 1024 * 1024;

    public function read(MediaAssetRecord $image): string
    {
        try {
            if (config('media.driver') === 'local') {
                if (! $image->publicId || str_contains($image->publicId, '..') || str_starts_with($image->publicId, '/')) {
                    throw new ImageWritingUnavailable('The image could not be read for AI writing.');
                }
                $stream = Storage::disk(config('media.local_disk'))->readStream($image->publicId);
                if ($stream === false) {
                    throw new ImageWritingUnavailable('The image could not be read for AI writing.');
                }
                try {
                    $bytes = stream_get_contents($stream, self::MAX_BYTES + 1);
                } finally {
                    fclose($stream);
                }
            } else {
                // Only fetch delivery URLs produced by our Cloudinary adapter; never arbitrary client URLs.
                $url = parse_url($image->secureUrl ?? '');
                if (($url['scheme'] ?? '') !== 'https' || ($url['host'] ?? '') !== 'res.cloudinary.com' || isset($url['port']) || isset($url['user'])) {
                    throw new ImageWritingUnavailable('The image could not be read for AI writing.');
                }
                $response = Http::connectTimeout(5)->timeout(15)
                    ->withOptions(['allow_redirects' => false, 'stream' => true])
                    ->get($image->secureUrl);
                if (! $response->successful()) {
                    throw new ImageWritingUnavailable('The image could not be read for AI writing.');
                }
                $stream = $response->toPsrResponse()->getBody();
                $bytes = '';
                try {
                    while (! $stream->eof() && strlen($bytes) <= self::MAX_BYTES) {
                        $chunk = $stream->read(min(8192, self::MAX_BYTES + 1 - strlen($bytes)));
                        if ($chunk === '') {
                            break;
                        }
                        $bytes .= $chunk;
                    }
                } finally {
                    $stream->close();
                }
            }
        } catch (Throwable) {
            throw new ImageWritingUnavailable('The image could not be read for AI writing.');
        }

        if ($bytes === false || strlen($bytes) > self::MAX_BYTES) {
            throw new ImageWritingUnavailable('AI writing supports images up to 5 MB.');
        }
        $metadata = @getimagesizefromstring($bytes);
        if ($metadata === false || ! in_array($metadata['mime'], ['image/jpeg', 'image/png', 'image/webp'], true) || $metadata['mime'] !== $image->mimeType) {
            throw new ImageWritingUnavailable('The image could not be read for AI writing.');
        }

        return $bytes;
    }
}
