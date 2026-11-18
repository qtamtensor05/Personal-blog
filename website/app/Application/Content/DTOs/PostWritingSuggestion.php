<?php

declare(strict_types=1);

namespace App\Application\Content\DTOs;

use App\Domain\Content\ValueObjects\TiptapDocument;
use InvalidArgumentException;

final readonly class PostWritingSuggestion
{
    public const FIELDS = ['title', 'slug', 'excerpt', 'seo_title', 'seo_description', 'image_alt', 'content'];

    public function __construct(
        public ?string $title,
        public ?string $slug,
        public ?string $excerpt,
        public ?string $seoTitle,
        public ?string $seoDescription,
        public ?string $imageAlt,
        public ?TiptapDocument $content,
    ) {}

    public static function fromArray(array $data, array $fields = self::FIELDS): self
    {
        if ($fields === [] || array_diff($fields, self::FIELDS) !== []) {
            throw new InvalidArgumentException('Invalid requested AI fields.');
        }
        foreach (['title' => 160, 'slug' => 160, 'excerpt' => 1000, 'seo_title' => 255, 'seo_description' => 500, 'image_alt' => 500] as $field => $max) {
            if (! in_array($field, $fields, true)) {
                continue;
            }
            if (! is_string($data[$field] ?? null) || trim($data[$field]) === '' || mb_strlen($data[$field]) > $max) {
                throw new InvalidArgumentException('Invalid AI field: '.$field);
            }
        }
        if (in_array('slug', $fields, true) && ! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $data['slug'])) {
            throw new InvalidArgumentException('Invalid AI slug.');
        }
        $content = null;
        if (in_array('content', $fields, true)) {
            $blocks = $data['blocks'] ?? null;
            if (! is_array($blocks) || ! array_is_list($blocks) || count($blocks) < 1 || count($blocks) > 40) {
                throw new InvalidArgumentException('Invalid AI content.');
            }
            $nodes = [];
            foreach ($blocks as $block) {
                if (! is_array($block) || ! in_array($block['type'] ?? null, ['heading', 'paragraph', 'bulletList', 'orderedList'], true)) {
                    throw new InvalidArgumentException('Invalid AI block.');
                }
                $type = $block['type'];
                if (in_array($type, ['bulletList', 'orderedList'], true)) {
                    $items = $block['items'] ?? null;
                    if (! is_array($items) || ! array_is_list($items) || count($items) < 1 || count($items) > 20) {
                        throw new InvalidArgumentException('Invalid AI list.');
                    }
                    $nodes[] = ['type' => $type, 'content' => array_map(fn ($text) => ['type' => 'listItem', 'content' => [self::textBlock('paragraph', $text)]], $items)];
                } else {
                    $nodes[] = self::textBlock($type, $block['text'] ?? null);
                }
            }
            $content = TiptapDocument::fromArray(['type' => 'doc', 'content' => $nodes]);
            if (mb_strlen($content->plainText(30001)) > 30000) {
                throw new InvalidArgumentException('AI content is too long.');
            }

        }
        $value = fn (string $key) => in_array($key, $fields, true) ? trim($data[$key]) : null;

        return new self($value('title'), $value('slug'), $value('excerpt'), $value('seo_title'), $value('seo_description'), $value('image_alt'), $content);
    }

    private static function textBlock(string $type, mixed $text): array
    {
        if (! is_string($text) || trim($text) === '' || mb_strlen($text) > 3000) {
            throw new InvalidArgumentException('Invalid AI text.');
        }
        $node = ['type' => $type, 'content' => [['type' => 'text', 'text' => trim($text)]]];
        if ($type === 'heading') {
            $node['attrs'] = ['level' => 2];
        }

        return $node;
    }

    public function toArray(): array
    {
        return array_filter(['title' => $this->title, 'slug' => $this->slug, 'excerpt' => $this->excerpt,
            'seo_title' => $this->seoTitle, 'seo_description' => $this->seoDescription,
            'image_alt' => $this->imageAlt, 'content' => $this->content?->toArray()], fn ($value) => $value !== null);
    }
}
