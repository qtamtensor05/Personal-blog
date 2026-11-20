<?php

declare(strict_types=1);

namespace App\Domain\Content\ValueObjects;

use InvalidArgumentException;

final readonly class PostDraftMetadata
{
    public function __construct(
        public ?string $slug,
        public ?string $seoTitle,
        public ?string $seoDescription,
    ) {
        if (($slug !== null && $slug !== '' && (! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug) || mb_strlen($slug) > 160)) || mb_strlen($seoTitle ?? '') > 255 || mb_strlen($seoDescription ?? '') > 500) {
            throw new InvalidArgumentException('The draft metadata is invalid.');
        }
    }

    public function toArray(): array
    {
        return ['slug' => $this->slug, 'seo_title' => $this->seoTitle, 'seo_description' => $this->seoDescription];
    }

    public static function fromArray(array $data): self
    {
        return new self($data['slug'] ?? null, $data['seo_title'] ?? null, $data['seo_description'] ?? null);
    }
}
