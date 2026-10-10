<?php

namespace App\Modules\Content\Rendering;

use App\Shared\Contracts\RichTextRenderer;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class SafeTiptapRenderer implements RichTextRenderer
{
    public function render(array $document): string
    {
        if (($document['type'] ?? null) !== 'doc' || ! is_array($document['content'] ?? [])) {
            throw new InvalidArgumentException('Tài liệu Tiptap không hợp lệ.');
        }

        return collect($document['content'])
            ->map(fn (array $node): string => $this->node($node))
            ->implode('');
    }

    private function node(array $node): string
    {
        $type = $node['type'] ?? '';
        $content = collect($node['content'] ?? [])
            ->map(fn (array $child): string => $this->node($child))
            ->implode('');
        $attrs = $node['attrs'] ?? [];

        return match ($type) {
            'text' => $this->text($node),
            'paragraph' => "<p>{$content}</p>",
            'heading' => '<h'.
                max(1, min(6, (int) ($attrs['level'] ?? 2))).
                '>'.
                $content.
                '</h'.
                max(1, min(6, (int) ($attrs['level'] ?? 2))).
                '>',
            'bulletList' => "<ul>{$content}</ul>",
            'orderedList' => "<ol>{$content}</ol>",
            'listItem' => "<li>{$content}</li>",
            'blockquote' => "<blockquote>{$content}</blockquote>",
            'hardBreak' => '<br>',
            'image' => $this->image($attrs),
            'table' => "<table>{$content}</table>",
            'tableRow' => "<tr>{$content}</tr>",
            'tableHeader' => "<th>{$content}</th>",
            'tableCell' => "<td>{$content}</td>",
            default => throw new InvalidArgumentException(
                "Node Tiptap không được hỗ trợ: {$type}",
            ),
        };
    }

    private function text(array $node): string
    {
        $value = e((string) ($node['text'] ?? ''));
        foreach ($node['marks'] ?? [] as $mark) {
            $value = match ($mark['type'] ?? '') {
                'bold' => "<strong>{$value}</strong>",
                'italic' => "<em>{$value}</em>",
                'strike' => "<s>{$value}</s>",
                'code' => "<code>{$value}</code>",
                'link' => $this->link($value, (string) data_get($mark, 'attrs.href', '')),
                default => $value,
            };
        }

        return $value;
    }

    private function link(string $content, string $url): string
    {
        if (! Str::startsWith($url, ['https://', 'http://', '/', '#', 'mailto:'])) {
            return $content;
        }

        return '<a href="'.e($url).'" rel="noopener noreferrer">'.$content.'</a>';
    }

    private function image(array $attrs): string
    {
        $mediaId = filter_var($attrs['mediaId'] ?? null, FILTER_VALIDATE_INT);
        if (! $mediaId) {
            throw new InvalidArgumentException('Ảnh phải tham chiếu media_id hợp lệ.');
        }
        $media = \DB::table('media_assets')
            ->where(['id' => $mediaId, 'status' => 'READY'])
            ->first();
        if (! $media) {
            throw new InvalidArgumentException('Không tìm thấy ảnh sẵn sàng.');
        }

        return '<img src="'.
            e($media->secure_url).
            '" alt="'.
            e((string) ($attrs['alt'] ?? ($media->alt_text ?? ''))).
            '">';
    }
}
