import { Editor, Node } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';
import Image from '@tiptap/extension-image';

const MediaImage = Image.extend({
    addAttributes() {
        return {
            ...this.parent?.(),
            mediaId: {
                default: null,
                parseHTML: (element) => element.getAttribute('data-media-id'),
                renderHTML: (attributes) => ({ 'data-media-id': attributes.mediaId }),
            },
        };
    },
});

const Layout = Node.create({
    name: 'layout',
    group: 'block',
    content: 'layoutCell layoutCell+',
    defining: true,
    addAttributes: () => ({ columns: { default: 2 } }),
    parseHTML: () => [{ tag: 'div[data-template-layout]' }],
    renderHTML: ({ HTMLAttributes }) => [
        'div',
        {
            ...HTMLAttributes,
            'data-template-layout': '',
            class: `template-layout template-layout-${HTMLAttributes.columns}`,
        },
        0,
    ],
});

const LayoutCell = Node.create({
    name: 'layoutCell',
    group: 'block',
    content: 'block+',
    isolating: true,
    parseHTML: () => [{ tag: 'div[data-template-layout-cell]' }],
    renderHTML: () => [
        'div',
        { 'data-template-layout-cell': '', class: 'template-layout-cell' },
        0,
    ],
});

function withImageSources(node, mediaUrls) {
    if (node.type === 'image' && node.attrs?.mediaId)
        node.attrs.src = mediaUrls[node.attrs.mediaId] ?? '';
    (node.content ?? []).forEach((child) => withImageSources(child, mediaUrls));
    return node;
}

function initTemplateEditor(root) {
    const config = JSON.parse(root.querySelector('[data-editor-config]').textContent);
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const status = root.querySelector('[data-editor-status]');
    const fileInput = root.querySelector('[data-template-image-input]');
    const editor = new Editor({
        element: root.querySelector('[data-template-content-editor]'),
        extensions: [
            StarterKit.configure({
                heading: { levels: [1, 2, 3, 4] },
                link: { openOnClick: false },
            }),
            MediaImage,
            Layout,
            LayoutCell,
        ],
        content: withImageSources(config.content, config.mediaUrls ?? {}),
        editorProps: {
            attributes: {
                class: 'template-editor min-h-[32rem] focus:outline-none',
                'aria-label': 'Template content editor',
            },
        },
    });

    const setStatus = (text, error = false) => {
        status.textContent = text;
        status.classList.toggle('text-red-500', error);
    };

    const insertLayout = (columns) =>
        editor
            .chain()
            .focus()
            .insertContent({
                type: 'layout',
                attrs: { columns },
                content: Array.from({ length: columns }, () => ({
                    type: 'layoutCell',
                    content: [{ type: 'paragraph' }],
                })),
            })
            .run();

    const commands = {
        bold: () => editor.chain().focus().toggleBold().run(),
        italic: () => editor.chain().focus().toggleItalic().run(),
        h1: () => editor.chain().focus().toggleHeading({ level: 1 }).run(),
        h2: () => editor.chain().focus().toggleHeading({ level: 2 }).run(),
        h3: () => editor.chain().focus().toggleHeading({ level: 3 }).run(),
        bulletList: () => editor.chain().focus().toggleBulletList().run(),
        orderedList: () => editor.chain().focus().toggleOrderedList().run(),
        quote: () => editor.chain().focus().toggleBlockquote().run(),
        divider: () => editor.chain().focus().setHorizontalRule().run(),
        columns2: () => insertLayout(2),
        columns3: () => insertLayout(3),
        image: () => fileInput.click(),
    };

    root.querySelectorAll('[data-template-command]').forEach((button) =>
        button.addEventListener('click', () => commands[button.dataset.templateCommand]?.()),
    );

    fileInput.addEventListener('change', async () => {
        const file = fileInput.files[0];
        fileInput.value = '';
        if (!file) return;
        const form = new FormData();
        form.append('file', file);
        form.append('scope', 'PLATFORM');
        setStatus('Uploading image to Cloudinary…');
        try {
            const response = await fetch(config.mediaUrl, {
                method: 'POST',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                body: form,
            });
            const body = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(body.message || 'Image upload failed.');
            const alt = await window.requestModalInput(
                'Image description',
                'Describe the image (alt text)',
                '',
            );
            editor
                .chain()
                .focus()
                .setImage({ src: body.data.url, alt, mediaId: body.data.id })
                .run();
            setStatus('Image uploaded.');
        } catch (error) {
            setStatus(error.message, true);
            window.showErrorModal?.(error.message);
        }
    });

    root.querySelector('[data-template-form]').addEventListener('submit', async (event) => {
        event.preventDefault();
        const form = event.currentTarget;
        setStatus('Saving draft…');
        const data = new FormData(form);
        const payload = {
            name: data.get('name'),
            slug: data.get('slug'),
            description: data.get('description') || null,
            demo_url: data.get('demo_url') || null,
            draft_data: {
                sections: [
                    { key: 'document', section: 'document', props: { content: editor.getJSON() } },
                ],
                designTokens: {
                    primaryColor: data.get('primaryColor'),
                    secondaryColor: data.get('secondaryColor'),
                    accentColor: data.get('accentColor'),
                    textColor: data.get('textColor'),
                    backgroundColor: data.get('backgroundColor'),
                    headingFont: data.get('headingFont'),
                    bodyFont: data.get('bodyFont'),
                },
                postLayout: {
                    variant: data.get('postLayoutVariant'),
                    contentWidth: data.get('postContentWidth'),
                    showBlogName: data.has('postShowBlogName'),
                    showPublishedDate: data.has('postShowPublishedDate'),
                    showCover: data.has('postShowCover'),
                },
            },
        };
        try {
            const response = await fetch(config.saveUrl, {
                method: config.method,
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
                body: JSON.stringify(payload),
            });
            const body = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(body.message || 'The template could not be saved.');
            window.location.href = config.successUrl;
        } catch (error) {
            setStatus(error.message, true);
            window.showErrorModal?.(error.message);
        }
    });
}

document.querySelectorAll('[data-template-editor]').forEach(initTemplateEditor);
