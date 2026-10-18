<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Infrastructure\Media\LocalMediaStorage;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class LocalMediaStorageTest extends TestCase
{
    public function test_it_stores_and_deletes_an_image_on_the_public_disk(): void
    {
        Storage::fake('public');
        $source = tempnam(sys_get_temp_dir(), 'avatar-');
        file_put_contents($source, base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=',
            true,
        ));

        try {
            $storage = new LocalMediaStorage('public');
            $stored = $storage->upload($source, 'personal-blog/users/7', 'avatar');

            self::assertSame('png', $stored->format);
            self::assertSame(1, $stored->width);
            self::assertSame(1, $stored->height);
            Storage::disk('public')->assertExists($stored->publicId);

            $storage->destroy($stored->publicId);
            Storage::disk('public')->assertMissing($stored->publicId);
        } finally {
            @unlink($source);
        }
    }
}
