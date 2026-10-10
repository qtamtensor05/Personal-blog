import { Editor } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';
import Image from '@tiptap/extension-image';
import { Table } from '@tiptap/extension-table';
import { TableRow } from '@tiptap/extension-table-row';
import { TableCell } from '@tiptap/extension-table-cell';
import { TableHeader } from '@tiptap/extension-table-header';

export function mountContentEditor(element, content, onChange) {
    const MediaImage = Image.extend({
        addAttributes() {
            return {
                ...this.parent?.(),
                mediaId: {
                    default: null,
                    parseHTML: (el) => el.dataset.mediaId,
                    renderHTML: (attrs) =>
                        attrs.mediaId ? { 'data-media-id': attrs.mediaId } : {},
                },
            };
        },
    });
    const editor = new Editor({
        element,
        content,
        extensions: [
            StarterKit,
            MediaImage,
            Table.configure({ resizable: true }),
            TableRow,
            TableHeader,
            TableCell,
        ],
        onUpdate: ({ editor: instance }) => onChange(instance.getJSON()),
    });
    return () => editor.destroy();
}
