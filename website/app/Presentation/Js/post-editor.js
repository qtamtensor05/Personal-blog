import { Editor } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';
import Image from '@tiptap/extension-image';

/**
 * Image node that references a media asset by ID.
 * `src` is only used for display in the editor; the server drops it and resolves
 * the URL from media_assets when rendering.
 */
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

function withImageSources(node, mediaUrls) {
    if (node.type === 'image' && node.attrs?.mediaId) {
        node.attrs.src = mediaUrls[node.attrs.mediaId] ?? '';
    }
    (node.content ?? []).forEach((child) => withImageSources(child, mediaUrls));
    return node;
}

function initPostEditor(root) {
    const config = JSON.parse(root.querySelector('[data-editor-config]').textContent);
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const status = root.querySelector('[data-save-status]');
    const titleInput = root.querySelector('[name="title"]');
    const excerptInput = root.querySelector('[name="excerpt"]');
    const fileInput = root.querySelector('[data-image-input]');

    const editor = new Editor({
        element: root.querySelector('[data-editor]'),
        extensions: [
            StarterKit.configure({
                heading: { levels: [2, 3, 4] },
                link: { openOnClick: false, protocols: ['http', 'https', 'mailto'] },
            }),
            MediaImage,
        ],
        content: withImageSources(config.content, config.mediaUrls),
        editorProps: {
            attributes: { class: 'post-content min-h-[24rem] focus:outline-none', 'aria-label': 'Post content' },
        },
    });

    let timer = null;
    let dirty = false;

    const setStatus = (text, isError = false) => {
        status.textContent = text;
        status.classList.toggle('text-red-500', isError);
    };

    async function save() {
        clearTimeout(timer);
        if (!dirty) return true;
        dirty = false;
        setStatus('Saving…');

        try {
            const response = await fetch(config.draftUrl, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({
                    title: titleInput.value,
                    // Pages have no excerpt field.
                    excerpt: excerptInput?.value || null,
                    content: editor.getJSON(),
                }),
            });

            if (!response.ok) {
                const body = await response.json().catch(() => ({}));
                dirty = true;
                setStatus(body.message ?? 'Could not save the draft.', true);
                return false;
            }

            setStatus('Draft saved');
            return true;
        } catch {
            dirty = true;
            setStatus('Offline. Changes are not saved yet.', true);
            return false;
        }
    }

    const scheduleSave = () => {
        dirty = true;
        setStatus('Unsaved changes');
        clearTimeout(timer);
        timer = setTimeout(save, 1500);
    };

    editor.on('update', scheduleSave);
    titleInput.addEventListener('input', scheduleSave);
    excerptInput?.addEventListener('input', scheduleSave);

    // Toolbar
    const commands = {
        bold: () => editor.chain().focus().toggleBold().run(),
        italic: () => editor.chain().focus().toggleItalic().run(),
        h2: () => editor.chain().focus().toggleHeading({ level: 2 }).run(),
        h3: () => editor.chain().focus().toggleHeading({ level: 3 }).run(),
        bulletList: () => editor.chain().focus().toggleBulletList().run(),
        orderedList: () => editor.chain().focus().toggleOrderedList().run(),
        blockquote: () => editor.chain().focus().toggleBlockquote().run(),
        codeBlock: () => editor.chain().focus().toggleCodeBlock().run(),
        link: async () => {
            const href = await window.requestModalInput('Add link', 'Link URL (https://…)', editor.getAttributes('link').href ?? '');
            if (href === null) return;
            if (href === '') {
                editor.chain().focus().unsetLink().run();
            } else {
                editor.chain().focus().extendMarkRange('link').setLink({ href }).run();
            }
        },
        image: () => fileInput.click(),
    };

    root.querySelectorAll('[data-command]').forEach((button) => {
        button.addEventListener('click', () => commands[button.dataset.command]?.());
    });

    // Image upload: BLOG-scoped media, then insert a node referencing its ID.
    fileInput.addEventListener('change', async () => {
        const file = fileInput.files[0];
        fileInput.value = '';
        if (!file) return;

        const form = new FormData();
        form.append('file', file);
        form.append('scope', 'BLOG');
        form.append('blog_id', config.blogId);
        setStatus('Uploading image…');

        try {
            const response = await fetch(config.mediaUrl, {
                method: 'POST',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                body: form,
            });
            const body = await response.json().catch(() => ({}));

            if (!response.ok) {
                setStatus(body.message ?? 'Image upload failed.', true);
                return;
            }

            const alt = await window.requestModalInput('Image description', 'Describe the image (alt text)', '');
            // setImage passes all attributes through to the inserted node.
            editor.chain().focus().setImage({ src: body.data.url, alt, mediaId: body.data.id }).run();
        } catch {
            setStatus('Image upload failed.', true);
        }
    });

    // Save pending changes before publishing.
    root.querySelector('[data-publish-form]')?.addEventListener('submit', async (event) => {
        if (!dirty) return;
        event.preventDefault();
        if (await save()) event.target.submit();
    });

    window.addEventListener('beforeunload', (event) => {
        if (dirty) event.preventDefault();
    });
}

document.querySelectorAll('[data-post-editor]').forEach(initPostEditor);
