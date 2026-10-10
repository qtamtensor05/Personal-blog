<?php

namespace App\Shared\Contracts;

interface CloudinaryGateway
{
    public function upload(string $localPath, array $options = []): array;

    public function destroy(string $publicId): bool;
}
