<?php

declare(strict_types=1);

namespace App\Application\Content\DTOs;

use DomainException;

/** A temporary analysis copy; the original media asset remains unchanged. */
final readonly class AnalysisImage
{
    public function __construct(public string $bytes, public string $mimeType)
    {
        $info = strlen($bytes) <= 1048576 ? @getimagesizefromstring($bytes) : false;
        if ($info === false || $info[0] > 1024 || $info[1] > 1024 || ($info['mime'] ?? null) !== $mimeType || ! in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            throw new DomainException('The optimized AI image is invalid.');
        }
    }
}
