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
    const coverInput = root.querySelector('[data-cover-image-input]');

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
            attributes: {
                class: 'post-content min-h-[24rem] focus:outline-none',
                'aria-label': 'Post content',
            },
        },
    });
    root.querySelector('[data-editor-fallback]').hidden = true;
    root.querySelector('[data-editor-loading]')?.setAttribute('hidden', '');

    let timer = null;
    let dirty = false;
    let saving = null;

    const setStatus = (text, isError = false) => {
        if (!status) return;
        status.textContent = text;
        status.classList.toggle('text-red-500', isError);
        status.classList.toggle('text-accent-primary', !isError);
    };

    const updateToolbar = () => {
        root.querySelectorAll('[data-command]').forEach((button) => {
            const command = button.dataset.command;
            const active =
                {
                    bold: () => editor.isActive('bold'),
                    italic: () => editor.isActive('italic'),
                    underline: () => editor.isActive('underline'),
                    strike: () => editor.isActive('strike'),
                    h2: () => editor.isActive('heading', { level: 2 }),
                    h3: () => editor.isActive('heading', { level: 3 }),
                    h4: () => editor.isActive('heading', { level: 4 }),
                    bulletList: () => editor.isActive('bulletList'),
                    orderedList: () => editor.isActive('orderedList'),
                    blockquote: () => editor.isActive('blockquote'),
                    codeBlock: () => editor.isActive('codeBlock'),
                    link: () => editor.isActive('link'),
                }[command]?.() ?? false;
            button.classList.toggle('is-active', active);
            button.setAttribute('aria-pressed', String(active));
            const canRun = {
                undo: () => editor.can().undo(),
                redo: () => editor.can().redo(),
            }[command];
            if (canRun) button.disabled = !canRun();
        });
    };

    async function save() {
        clearTimeout(timer);
        if (saving) {
            await saving;
            return dirty ? save() : true;
        }
        if (!dirty) return true;

        dirty = false;
        setStatus('Saving…');
        const payload = JSON.stringify({
            title: titleInput.value,
            // Pages have no excerpt field.
            excerpt: excerptInput?.value || null,
            content: editor.getJSON(),
        });

        saving = (async () => {
            try {
                const response = await fetch(config.draftUrl, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': csrf,
                    },
                    body: payload,
                });

                if (!response.ok) {
                    const body = await response.json().catch(() => ({}));
                    dirty = true;
                    setStatus(body.message ?? 'Could not save the draft.', true);
                    return false;
                }

                // A user may have edited while the request was in flight.
                if (dirty) {
                    setStatus('Unsaved changes');
                    return false;
                }
                setStatus('Draft saved');
                return true;
            } catch {
                dirty = true;
                setStatus('Offline. Changes are not saved yet.', true);
                return false;
            } finally {
                saving = null;
            }
        })();

        return saving;
    }

    const scheduleSave = () => {
        dirty = true;
        setStatus('Unsaved changes');
        clearTimeout(timer);
        timer = setTimeout(() => void save(), 1500);
    };

    editor.on('update', () => {
        scheduleSave();
        updateToolbar();
    });
    editor.on('selectionUpdate', updateToolbar);
    editor.on('transaction', updateToolbar);
    titleInput.addEventListener('input', scheduleSave);
    excerptInput?.addEventListener('input', scheduleSave);
    updateToolbar();

    // Toolbar commands are kept explicit so each action remains easy to audit.
    const commands = {
        paragraph: () => editor.chain().focus().setParagraph().run(),
        bold: () => editor.chain().focus().toggleBold().run(),
        italic: () => editor.chain().focus().toggleItalic().run(),
        underline: () => editor.chain().focus().toggleUnderline().run(),
        strike: () => editor.chain().focus().toggleStrike().run(),
        h2: () => editor.chain().focus().toggleHeading({ level: 2 }).run(),
        h3: () => editor.chain().focus().toggleHeading({ level: 3 }).run(),
        h4: () => editor.chain().focus().toggleHeading({ level: 4 }).run(),
        bulletList: () => editor.chain().focus().toggleBulletList().run(),
        orderedList: () => editor.chain().focus().toggleOrderedList().run(),
        blockquote: () => editor.chain().focus().toggleBlockquote().run(),
        codeBlock: () => editor.chain().focus().toggleCodeBlock().run(),
        horizontalRule: () => editor.chain().focus().setHorizontalRule().run(),
        undo: () => editor.chain().focus().undo().run(),
        redo: () => editor.chain().focus().redo().run(),
        link: async () => {
            const currentHref = editor.getAttributes('link').href ?? '';
            const href = await window.requestModalInput(
                'Add link',
                'Link URL (https://…)',
                currentHref,
            );
            if (href === null) return;
            const value = href.trim();
            if (!value) {
                editor.chain().focus().extendMarkRange('link').unsetLink().run();
                return;
            }
            // Only allow web links and email links; reject javascript:, data:, etc.
            if (!/^(https?:\/\/|mailto:)/i.test(value)) {
                setStatus('Use a link starting with https://, http:// or mailto:.', true);
                return;
            }
            editor.chain().focus().extendMarkRange('link').setLink({ href: value }).run();
        },
        image: () => fileInput.click(),
    };

    root.querySelectorAll('[data-command]').forEach((button) => {
        button.addEventListener('click', () => {
            void commands[button.dataset.command]?.();
        });
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

            const alt = await window.requestModalInput(
                'Image description',
                'Describe the image (alt text)',
                '',
            );
            // setImage passes all attributes through to the inserted node.
            editor
                .chain()
                .focus()
                .setImage({ src: body.data.url, alt, mediaId: body.data.id })
                .run();
        } catch {
            setStatus('Image upload failed.', true);
        }
    });

    coverInput?.addEventListener('change', async () => {
        const file = coverInput.files[0];
        coverInput.value = '';
        if (!file) return;
        const coverStatus = root.querySelector('[data-cover-status]');
        const form = new FormData();
        form.append('file', file);
        form.append('scope', 'BLOG');
        form.append('blog_id', config.blogId);
        coverStatus.textContent = 'Đang tải ảnh đại diện...';
        try {
            const response = await fetch(config.mediaUrl, {
                method: 'POST',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                body: form,
            });
            const body = await response.json().catch(() => ({}));
            if (!response.ok)
                throw new Error(
                    body.message ??
                        Object.values(body.errors ?? {})[0]?.[0] ??
                        'Không thể tải ảnh.',
                );
            root.querySelector('[data-cover-media-id]').value = body.data.id;
            const preview = root.querySelector('[data-cover-preview]');
            const image = document.createElement('img');
            image.src = body.data.url;
            image.alt = '';
            image.className = 'mb-2 aspect-video w-full rounded-md object-cover';
            preview.replaceChildren(image);
            coverStatus.textContent = 'Đã tải ảnh. Nhấn “Lưu thông tin” để áp dụng.';
        } catch (error) {
            coverStatus.textContent = error.message;
            coverStatus.classList.add('text-red-500');
        }
    });

    // Save pending changes before publishing.
    let publishing = false;
    root.querySelector('[data-publish-form]')?.addEventListener('submit', async (event) => {
        if (publishing) return;
        if (!dirty && !saving) return;
        event.preventDefault();
        const form = event.currentTarget;
        const button = form.querySelector('button[type=submit]');
        if (button) button.disabled = true;
        const saved = await save();
        if (saved) {
            publishing = true;
            form.requestSubmit();
        } else if (button) {
            button.disabled = false;
        }
    });

    window.addEventListener('beforeunload', (event) => {
        if (dirty || saving) event.preventDefault();
    });

    window.addEventListener(
        'pagehide',
        () => {
            clearTimeout(timer);
            editor.destroy();
        },
        { once: true },
    );
}

document.querySelectorAll('[data-post-editor]').forEach((root) => {
    try {
        initPostEditor(root);
    } catch (error) {
        console.error('Unable to initialize the post editor.', error);
        const loading = root.querySelector('[data-editor-loading]');
        const fallback = root.querySelector('[data-editor-fallback]');
        const status = root.querySelector('[data-save-status]');

        loading?.setAttribute('hidden', '');
        if (fallback) fallback.hidden = false;
        if (status) {
            status.textContent = 'Editor could not be initialized. Use the plain-text fallback.';
            status.classList.add('text-red-500');
        }
    }
});
