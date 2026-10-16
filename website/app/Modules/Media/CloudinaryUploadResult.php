<?php

namespace App\Modules\Media;

use RuntimeException;

/**
 * Validated view of the payload returned by the media provider. Anything the
 * provider sends is untrusted until it has passed through this object.
 */
final class CloudinaryUploadResult
{
    private const REQUIRED_KEYS = ['asset_id', 'public_id', 'secure_url'];

    private function __construct(
        public readonly string $assetId,
        public readonly string $publicId,
        public readonly string $secureUrl,
        private readonly array $payload,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     *
     * @throws RuntimeException
     */
    public static function fromPayload(array $payload): self
    {
        foreach (self::REQUIRED_KEYS as $key) {
            if (! is_string($payload[$key] ?? null) || trim($payload[$key]) === '') {
                throw new RuntimeException('Cloudinary trả về dữ liệu ảnh không đầy đủ.');
            }
        }

        if (
            strlen($payload['asset_id']) > 128
            || strlen($payload['public_id']) > 255
            || strlen($payload['secure_url']) > 2048
            || ! filter_var($payload['secure_url'], FILTER_VALIDATE_URL)
            || parse_url($payload['secure_url'], PHP_URL_SCHEME) !== 'https'
        ) {
            throw new RuntimeException('Thông tin ảnh từ Cloudinary không hợp lệ.');
        }

        return new self($payload['asset_id'], $payload['public_id'], $payload['secure_url'], $payload);
    }

    /**
     * Public id of a possibly invalid payload, so a half-failed upload can
     * still be cleaned up at the provider.
     *
     * @param  array<string, mixed>|null  $payload
     */
    public static function salvagePublicId(?array $payload): ?string
    {
        $publicId = $payload['public_id'] ?? null;

        return is_string($publicId) && strlen($publicId) <= 255 ? $publicId : null;
    }

    /**
     * Columns to store once the asset is ready.
     *
     * @return array<string, mixed>
     */
    public function toReadyColumns(string $fallbackCloudName, ?int $fallbackBytes): array
    {
        return [
            'cloud_name' => $this->payload['cloud_name'] ?? $fallbackCloudName,
            'cloudinary_asset_id' => $this->assetId,
            'cloudinary_public_id' => $this->publicId,
            'cloudinary_version' => $this->payload['version'] ?? null,
            'secure_url' => $this->secureUrl,
            'format' => $this->payload['format'] ?? null,
            'width' => $this->payload['width'] ?? null,
            'height' => $this->payload['height'] ?? null,
            'bytes' => $this->payload['bytes'] ?? $fallbackBytes,
            'status' => 'READY',
            'updated_at' => now(),
        ];
    }
}
