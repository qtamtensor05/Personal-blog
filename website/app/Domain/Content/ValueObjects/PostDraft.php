<?php

declare(strict_types=1);

namespace App\Domain\Content\ValueObjects;

use InvalidArgumentException;
use JsonSerializable;

/**
 * Work-in-progress version of a post, stored in posts.draft_data.
 */
final readonly class PostDraft implements JsonSerializable
{
    public function __construct(
        public string $title,
        public ?string $excerpt,
        public TiptapDocument $content,
        public ?PostDraftMetadata $metadata = null,
    ) {
        $length = mb_strlen(trim($title));

        if ($length === 0 || $length > 160) {
            throw new InvalidArgumentException('Title must contain between 1 and 160 characters.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        $data = [
            'title' => $this->title,
            'excerpt' => $this->excerpt,
            'content' => $this->content->toArray(),
        ];
        if ($this->metadata !== null) {
            $data['metadata'] = $this->metadata->toArray();
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            title: (string) $data['title'],
            excerpt: isset($data['excerpt']) ? (string) $data['excerpt'] : null,
            content: TiptapDocument::fromArray((array) $data['content']),
            metadata: isset($data['metadata']) && is_array($data['metadata']) ? PostDraftMetadata::fromArray($data['metadata']) : null,
        );
    }
}
