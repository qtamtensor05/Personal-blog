<?php

declare(strict_types=1);

namespace App\Domain\Templates\ValueObjects;

use InvalidArgumentException;
use JsonSerializable;

final readonly class TemplatePostLayout implements JsonSerializable
{
    private const VARIANTS = ['editorial', 'card', 'minimal'];

    private const WIDTHS = ['narrow', 'standard', 'wide'];

    public function __construct(
        public string $variant,
        public string $contentWidth,
        public bool $showBlogName,
        public bool $showPublishedDate,
        public bool $showCover,
    ) {
        if (! in_array($variant, self::VARIANTS, true)) {
            throw new InvalidArgumentException("Invalid post layout variant: {$variant}");
        }

        if (! in_array($contentWidth, self::WIDTHS, true)) {
            throw new InvalidArgumentException("Invalid post content width: {$contentWidth}");
        }
    }

    /** @return array{variant: string, contentWidth: string, showBlogName: bool, showPublishedDate: bool, showCover: bool} */
    public function jsonSerialize(): array
    {
        return [
            'variant' => $this->variant,
            'contentWidth' => $this->contentWidth,
            'showBlogName' => $this->showBlogName,
            'showPublishedDate' => $this->showPublishedDate,
            'showCover' => $this->showCover,
        ];
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $default = self::default();

        return new self(
            variant: (string) ($data['variant'] ?? $default->variant),
            contentWidth: (string) ($data['contentWidth'] ?? $default->contentWidth),
            showBlogName: (bool) ($data['showBlogName'] ?? $default->showBlogName),
            showPublishedDate: (bool) ($data['showPublishedDate'] ?? $default->showPublishedDate),
            showCover: (bool) ($data['showCover'] ?? $default->showCover),
        );
    }

    public static function default(): self
    {
        return new self(
            variant: 'editorial',
            contentWidth: 'standard',
            showBlogName: true,
            showPublishedDate: true,
            showCover: true,
        );
    }
}
