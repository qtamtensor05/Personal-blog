<?php

declare(strict_types=1);

namespace App\Infrastructure\Media;

use App\Application\Media\Contracts\MediaStorage;
use App\Application\Media\DTOs\StoredMedia;
use App\Application\Media\Exceptions\MediaStorageFailed;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Signed server-side uploads through the Cloudinary REST API.
 * The API secret never leaves the backend.
 */
final readonly class CloudinaryMediaStorage implements MediaStorage
{
    private const API = 'https://api.cloudinary.com/v1_1';

    private string $cloudName;

    private string $apiKey;

    private string $apiSecret;

    public function __construct(string $cloudinaryUrl)
    {
        $parts = parse_url($cloudinaryUrl);

        if (
            ($parts['scheme'] ?? null) !== 'cloudinary' ||
            empty($parts['host']) ||
            empty($parts['user']) ||
            empty($parts['pass'])
        ) {
            throw new RuntimeException(
                'CLOUDINARY_URL must look like cloudinary://<api_key>:<api_secret>@<cloud_name>.',
            );
        }

        $this->cloudName = $parts['host'];
        $this->apiKey = urldecode($parts['user']);
        $this->apiSecret = urldecode($parts['pass']);
    }

    public function cloudName(): string
    {
        return $this->cloudName;
    }

    public function upload(string $localPath, string $folder, string $publicId): StoredMedia
    {
        $params = $this->sign([
            'folder' => $folder,
            'public_id' => $publicId,
            'overwrite' => 'false',
            'timestamp' => (string) time(),
        ]);

        $handle = fopen($localPath, 'r');

        if ($handle === false) {
            throw new MediaStorageFailed('Uploaded file could not be read.');
        }

        try {
            $response = Http::timeout(60)
                ->attach('file', $handle, basename($localPath))
                ->post($this->endpoint('upload'), $params);
        } catch (ConnectionException $exception) {
            throw new MediaStorageFailed('Cloudinary is unreachable.', previous: $exception);
        } finally {
            if (is_resource($handle)) {
                fclose($handle);
            }
        }

        $this->ensureSuccessful($response);
        $body = $response->json();

        return new StoredMedia(
            assetId: (string) $body['asset_id'],
            publicId: (string) $body['public_id'],
            version: (int) $body['version'],
            resourceType: (string) $body['resource_type'],
            deliveryType: (string) $body['type'],
            secureUrl: (string) $body['secure_url'],
            format: $body['format'] ?? null,
            width: isset($body['width']) ? (int) $body['width'] : null,
            height: isset($body['height']) ? (int) $body['height'] : null,
            bytes: (int) $body['bytes'],
        );
    }

    public function destroy(string $publicId): void
    {
        $params = $this->sign([
            'public_id' => $publicId,
            'invalidate' => 'true',
            'timestamp' => (string) time(),
        ]);

        try {
            $response = Http::asForm()->timeout(30)->post($this->endpoint('destroy'), $params);
        } catch (ConnectionException $exception) {
            throw new MediaStorageFailed('Cloudinary is unreachable.', previous: $exception);
        }

        $this->ensureSuccessful($response);

        if (! in_array($response->json('result'), ['ok', 'not found'], true)) {
            throw new MediaStorageFailed('Cloudinary did not delete the asset.');
        }
    }

    /**
     * @param  array<string, string>  $params
     * @return array<string, string>
     */
    private function sign(array $params): array
    {
        ksort($params);

        $toSign = implode('&', array_map(
            fn (string $key, string $value) => "{$key}={$value}",
            array_keys($params),
            $params,
        ));

        return $params + [
            'api_key' => $this->apiKey,
            'signature' => sha1($toSign.$this->apiSecret),
        ];
    }

    private function endpoint(string $action): string
    {
        return self::API."/{$this->cloudName}/image/{$action}";
    }

    private function ensureSuccessful(Response $response): void
    {
        if ($response->failed()) {
            // Cloudinary error messages never contain the secret, but keep them out of user output.
            throw new MediaStorageFailed(
                'Cloudinary request failed: '.($response->json('error.message') ?? $response->status()),
            );
        }
    }
}
