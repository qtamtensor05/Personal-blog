<?php

namespace App\Modules\Media\Infrastructure;

use App\Shared\Contracts\CloudinaryGateway;
use Cloudinary\Cloudinary;
use RuntimeException;

final class CloudinarySdkGateway implements CloudinaryGateway
{
    private function client(): Cloudinary
    {
        $url = config('services.cloudinary.url');
        if (! is_string($url) || $url === '') {
            throw new RuntimeException('Cloudinary chưa được cấu hình.');
        }

        return new Cloudinary($url);
    }

    public function upload(string $localPath, array $options = []): array
    {
        $result = $this->client()->uploadApi()->upload($localPath, $options);

        return $result->getArrayCopy();
    }

    public function destroy(string $publicId): bool
    {
        $result = $this->client()->uploadApi()->destroy($publicId);

        return in_array($result['result'] ?? null, ['ok', 'not found'], true);
    }
}
