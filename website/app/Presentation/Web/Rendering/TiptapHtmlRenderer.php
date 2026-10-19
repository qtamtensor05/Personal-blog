<?php

declare(strict_types=1);

namespace App\Presentation\Web\Rendering;

use App\Domain\Content\ValueObjects\TiptapDocument;

/**
 * Renders a normalized Tiptap document to HTML. Every text and attribute is escaped;
 * only the node types allowed by TiptapDocument are produced.
 */
final class TiptapHtmlRenderer
{
    /**
     * @param  array<int, array{url: string, width: ?int, height: ?int}>  $media  mediaId => asset
     */
    public function render(TiptapDocument $document, array $media): string
    {
        return $this->children($document->toArray()['content'] ?? [], $media);
    }

    /**
     * @param  list<array<string, mixed>>  $nodes
     * @param  array<int, array{url: string, width: ?int, height: ?int}>  $media
     */
    private function children(array $nodes, array $media): string
    {
        return implode('', array_map(fn (array $node) => $this->node($node, $media), $nodes));
    }

    /**
     * @param  array<string, mixed>  $node
     * @param  array<int, array{url: string, width: ?int, height: ?int}>  $media
     */
    private function node(array $node, array $media): string
    {
        $inner = fn () => $this->children($node['content'] ?? [], $media);

        return match ($node['type']) {
            'text' => $this->text($node),
            'hardBreak' => '<br>',
            'horizontalRule' => '<hr>',
            'paragraph' => '<p'.$this->alignment($node).'>'.$inner().'</p>',
            'heading' => sprintf(
                '<h%1$d%2$s>%3$s</h%1$d>',
                $node['attrs']['level'],
                $this->alignment($node),
                $inner(),
            ),
            'bulletList' => '<ul>'.$inner().'</ul>',
            'orderedList' => sprintf('<ol start="%d">%s</ol>', $node['attrs']['start'], $inner()),
            'listItem' => '<li>'.$inner().'</li>',
            'blockquote' => '<blockquote>'.$inner().'</blockquote>',
            'codeBlock' => sprintf(
                '<pre><code%s>%s</code></pre>',
                $node['attrs']['language']
                    ? ' class="language-'.e($node['attrs']['language']).'"'
                    : '',
                $inner(),
            ),
            'image' => $this->image($node, $media),
            'layout' => '<div class="template-layout template-layout-'.
                $this->safeColumns($node).
                '">'.
                $inner().
                '</div>',
            'layoutCell' => '<div class="template-layout-cell">'.$inner().'</div>',
            default => '',
        };
    }

    /** @param array<string, mixed> $node */
    private function safeColumns(array $node): int
    {
        return max(2, min(3, (int) ($node['attrs']['columns'] ?? 2)));
    }

    /** @param array<string, mixed> $node */
    private function alignment(array $node): string
    {
        $alignment = $node['attrs']['textAlign'] ?? null;

        return in_array($alignment, ['left', 'center', 'right', 'justify'], true)
            ? ' style="text-align:'.$alignment.'"'
            : '';
    }

    /**
     * @param  array<string, mixed>  $node
     */
    private function text(array $node): string
    {
        $html = e($node['text']);

        foreach ($node['marks'] ?? [] as $mark) {
            $html = match ($mark['type']) {
                'bold' => "<strong>{$html}</strong>",
                'italic' => "<em>{$html}</em>",
                'strike' => "<s>{$html}</s>",
                'underline' => "<u>{$html}</u>",
                'code' => "<code>{$html}</code>",
                'link' => '<a href="'.
                    e($mark['attrs']['href']).
                    '" rel="noopener nofollow ugc">'.
                    $html.
                    '</a>',
                default => $html,
            };
        }

        return $html;
    }

    /**
     * Images whose media is missing, deleted or from another blog are skipped.
     *
     * @param  array<string, mixed>  $node
     * @param  array<int, array{url: string, width: ?int, height: ?int}>  $media
     */
    private function image(array $node, array $media): string
    {
        $asset = $media[$node['attrs']['mediaId']] ?? null;

        if ($asset === null) {
            return '';
        }

        $size =
            $asset['width'] !== null && $asset['height'] !== null
                ? sprintf(' width="%d" height="%d"', $asset['width'], $asset['height'])
                : '';

        return sprintf(
            '<figure><img src="%s" alt="%s" loading="lazy"%s></figure>',
            e($asset['url']),
            e($node['attrs']['alt'] ?? ''),
            $size,
        );
    }
}
