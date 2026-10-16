<?php

namespace Tests\Unit\Modules\Media;

use App\Modules\Media\CloudinaryUploadResult;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

class CloudinaryUploadResultTest extends TestCase
{
    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return $overrides + [
            'asset_id' => 'asset-1',
            'public_id' => 'blogs/1/photo',
            'secure_url' => 'https://res.cloudinary.com/demo/photo.jpg',
            'width' => 800,
        ];
    }

    #[Test]
    public function it_accepts_a_complete_https_payload(): void
    {
        $result = CloudinaryUploadResult::fromPayload($this->payload());

        $columns = $result->toReadyColumns('fallback-cloud', 123);

        $this->assertSame('READY', $columns['status']);
        $this->assertSame('fallback-cloud', $columns['cloud_name']);
        $this->assertSame(123, $columns['bytes']);
        $this->assertSame(800, $columns['width']);
    }

    /**
     * @return array<string, array{0: array<string, mixed>}>
     */
    public static function invalidPayloads(): array
    {
        return [
            'missing asset id' => [['asset_id' => '']],
            'missing public id' => [['public_id' => null]],
            'non-string secure url' => [['secure_url' => 42]],
            'plain http url' => [['secure_url' => 'http://example.com/a.jpg']],
            'not a url' => [['secure_url' => 'not-a-url']],
            'oversized public id' => [['public_id' => str_repeat('a', 256)]],
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    #[Test]
    #[DataProvider('invalidPayloads')]
    public function it_rejects_incomplete_or_unsafe_payloads(array $overrides): void
    {
        $this->expectException(RuntimeException::class);

        CloudinaryUploadResult::fromPayload($this->payload($overrides));
    }

    #[Test]
    public function it_salvages_a_public_id_only_when_it_is_safe_to_store(): void
    {
        $this->assertSame('abc', CloudinaryUploadResult::salvagePublicId(['public_id' => 'abc']));
        $this->assertNull(CloudinaryUploadResult::salvagePublicId(null));
        $this->assertNull(CloudinaryUploadResult::salvagePublicId(['public_id' => 12]));
        $this->assertNull(CloudinaryUploadResult::salvagePublicId(['public_id' => str_repeat('a', 256)]));
    }
}
