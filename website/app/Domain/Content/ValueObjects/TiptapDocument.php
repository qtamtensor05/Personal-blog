<?php

declare(strict_types=1);

namespace App\Domain\Content\ValueObjects;

use InvalidArgumentException;

/**
 * A Tiptap (ProseMirror) JSON document, normalized to an allow-list of nodes,
 * marks and attributes. Anything else is dropped, so stored content can be
 * rendered without trusting the client.
 */
final readonly class TiptapDocument
{
    private const BLOCK_NODES = [
        'paragraph',
        'heading',
        'bulletList',
        'orderedList',
        'listItem',
        'blockquote',
        'codeBlock',
        'horizontalRule',
        'image',
        'layout',
        'layoutCell',
    ];

    private const INLINE_NODES = ['text', 'hardBreak'];

    private const MARKS = ['bold', 'italic', 'strike', 'code', 'underline', 'link'];

    private const MAX_DEPTH = 20;

    private const MAX_NODES = 20000;

    /**
     * @param  array<string, mixed>  $doc  Already normalized.
     */
    private function __construct(private array $doc) {}

    public static function empty(): self
    {
        return new self(['type' => 'doc', 'content' => [['type' => 'paragraph']]]);
    }

    /**
     * @param  array<string, mixed>  $raw
     */
    public static function fromArray(array $raw): self
    {
        if (($raw['type'] ?? null) !== 'doc') {
            throw new InvalidArgumentException('Content must be a Tiptap document.');
        }

        $count = 0;
        $content = self::normalizeChildren($raw['content'] ?? [], 1, $count);

        return new self([
            'type' => 'doc',
            'content' => $content === [] ? [['type' => 'paragraph']] : $content,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->doc;
    }

    /**
     * Media IDs referenced by image nodes.
     *
     * @return list<int>
     */
    public function mediaIds(): array
    {
        $ids = [];
        $walk = function (array $node) use (&$walk, &$ids): void {
            if (($node['type'] ?? null) === 'image') {
                $ids[] = (int) $node['attrs']['mediaId'];
            }

            foreach ($node['content'] ?? [] as $child) {
                $walk($child);
            }
        };
        $walk($this->doc);

        return array_values(array_unique($ids));
    }

    public function plainText(int $limit = 300): string
    {
        $parts = [];
        $walk = function (array $node) use (&$walk, &$parts): void {
            if (($node['type'] ?? null) === 'text') {
                $parts[] = $node['text'];
            }

            foreach ($node['content'] ?? [] as $child) {
                $walk($child);
            }
        };
        $walk($this->doc);

        $text = trim(preg_replace('/\s+/u', ' ', implode(' ', $parts)) ?? '');

        return mb_strlen($text) > $limit ? rtrim(mb_substr($text, 0, $limit)).'…' : $text;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function normalizeChildren(mixed $children, int $depth, int &$count): array
    {
        if (! is_array($children)) {
            return [];
        }

        if ($depth > self::MAX_DEPTH) {
            throw new InvalidArgumentException('Content is nested too deeply.');
        }

        $result = [];

        foreach ($children as $child) {
            if (! is_array($child) || ! is_string($child['type'] ?? null)) {
                continue;
            }

            if (++$count > self::MAX_NODES) {
                throw new InvalidArgumentException('Content is too large.');
            }

            $node = self::normalizeNode($child, $depth, $count);

            if ($node !== null) {
                $result[] = $node;
            }
        }

        return $result;
    }

    /**
     * @param  array<string, mixed>  $node
     * @return array<string, mixed>|null
     */
    private static function normalizeNode(array $node, int $depth, int &$count): ?array
    {
        $type = $node['type'];

        if ($type === 'text') {
            $text = $node['text'] ?? '';

            if (! is_string($text) || $text === '') {
                return null;
            }

            $result = ['type' => 'text', 'text' => $text];
            $marks = self::normalizeMarks($node['marks'] ?? []);

            return $marks === [] ? $result : $result + ['marks' => $marks];
        }

        if ($type === 'hardBreak' || $type === 'horizontalRule') {
            return ['type' => $type];
        }

        if ($type === 'layout' || $type === 'layoutCell') {
            $children = self::normalizeChildren($node['content'] ?? [], $depth + 1, $count);

            if ($type === 'layout' && count($children) < 2) {
                return null;
            }

            if ($type === 'layoutCell' && $children === []) {
                return null;
            }

            return [
                'type' => $type,
                'attrs' => $type === 'layout'
                        ? [
                            'columns' => max(
                                2,
                                min(3, (int) ($node['attrs']['columns'] ?? count($children))),
                            ),
                        ]
                        : [],
                'content' => $children,
            ];
        }

        if ($type === 'image') {
            $mediaId = $node['attrs']['mediaId'] ?? null;

            // Images must point at a media asset; the URL is resolved server-side at render time.
            if (! is_int($mediaId) && ! (is_string($mediaId) && ctype_digit($mediaId))) {
                return null;
            }

            $alt = $node['attrs']['alt'] ?? null;

            return [
                'type' => 'image',
                'attrs' => [
                    'mediaId' => (int) $mediaId,
                    'alt' => is_string($alt) ? mb_substr($alt, 0, 500) : null,
                ],
            ];
        }

        if (! in_array($type, self::BLOCK_NODES, true)) {
            return null;
        }

        $result = ['type' => $type];

        if ($type === 'heading') {
            $level = (int) ($node['attrs']['level'] ?? 2);
            $result['attrs'] = ['level' => max(2, min(4, $level))];
        }

        if ($type === 'orderedList') {
            $start = (int) ($node['attrs']['start'] ?? 1);
            $result['attrs'] = ['start' => max(1, $start)];
        }

        if ($type === 'codeBlock') {
            $language = $node['attrs']['language'] ?? null;
            $result['attrs'] = [
                'language' => is_string($language) && preg_match('/^[a-z0-9+#-]{1,30}$/i', $language)
                        ? $language
                        : null,
            ];
        }

        $children = self::normalizeChildren($node['content'] ?? [], $depth + 1, $count);

        if ($children !== []) {
            $result['content'] = $children;
        }

        return $result;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function normalizeMarks(mixed $marks): array
    {
        if (! is_array($marks)) {
            return [];
        }

        $result = [];

        foreach ($marks as $mark) {
            $type = is_array($mark) ? $mark['type'] ?? null : null;

            if (! in_array($type, self::MARKS, true)) {
                continue;
            }

            if ($type === 'link') {
                $href = $mark['attrs']['href'] ?? null;

                // Only http(s) and mailto links; blocks javascript: and data: URLs.
                if (
                    ! is_string($href) ||
                    ! preg_match('#^(https?://|mailto:)#i', $href) ||
                    mb_strlen($href) > 2048
                ) {
                    continue;
                }

                $result[] = ['type' => 'link', 'attrs' => ['href' => $href]];

                continue;
            }

            $result[] = ['type' => $type];
        }

        return $result;
    }
}
