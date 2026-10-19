import { Editor, Extension } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';
import Image from '@tiptap/extension-image';

const TextAlign = Extension.create({
    name: 'textAlign',
    addGlobalAttributes() {
        return [{
            types: ['paragraph', 'heading'],
            attributes: {
                textAlign: {
                    default: null,
                    parseHTML: (element) => element.style.textAlign || null,
                    renderHTML: (attributes) => attributes.textAlign
                        ? { style: `text-align: ${attributes.textAlign}` }
                        : {},
                },
            },
        }];
    },
    addCommands() {
        return {
            setTextAlign: (textAlign) => ({ commands }) =>
                commands.updateAttributes('paragraph', { textAlign }) ||
                commands.updateAttributes('heading', { textAlign }),
        };
    },
});

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
    const titleInput = root.querySelector('[data-publish-title-source]');
    const excerptInput = root.querySelector('[name="excerpt"]');
    const fileInput = root.querySelector('[data-image-input]');
    const imageButton = root.querySelector('[data-command="image"]');
    const imageUploadStatus = root.querySelector('[data-image-upload-status]');
    const coverInput = root.querySelector('[data-cover-image-input]');
    const publishForm = root.querySelector('[data-publish-form]');
    const contentInput = root.querySelector('[data-post-content]');
    const previewToggle = root.querySelector('[data-preview-toggle]');
    const toolbar = root.querySelector('[role="toolbar"]');

    const editorElement = root.querySelector('[data-editor]');
    const editor = new Editor({
        element: editorElement,
        editable: true,
        extensions: [
            StarterKit.configure({
                heading: { levels: [2, 3, 4] },
                link: { openOnClick: false, protocols: ['http', 'https', 'mailto'] },
            }),
            MediaImage,
            TextAlign,
        ],
        content: withImageSources(config.content, config.mediaUrls),
        editorProps: {
            attributes: {
                class: 'post-content min-h-[24rem] focus:outline-none',
                'aria-label': 'Post content',
            },
        },
    });
    editor.setEditable(true);
    editor.view.dom.setAttribute('contenteditable', 'true');
    editor.view.dom.setAttribute('tabindex', '0');
    status.textContent = 'Trình soạn thảo đã sẵn sàng.';
    if (contentInput) {
        contentInput.value = JSON.stringify(editor.getJSON());
        publishForm?.addEventListener('submit', () => {
            contentInput.value = JSON.stringify(editor.getJSON());
            root.querySelector('[data-publish-title]').value = titleInput.value;
            root.querySelector('[data-publish-excerpt]').value = excerptInput?.value ?? '';
            root.querySelector('[data-publish-slug]').value = root.querySelector('[data-publish-slug-source]')?.value ?? '';
            root.querySelector('[data-publish-cover]').value = root.querySelector('[data-cover-media-id]')?.value ?? '';
            root.querySelector('[data-publish-featured]').value = root.querySelector('[data-publish-featured-source]')?.checked ? '1' : '0';
            root.querySelectorAll('[data-publish-category-source]').forEach((input) => {
                const hidden = root.querySelector(`[data-publish-category="${input.value}"]`);
                if (input.checked && !hidden) {
                    const field = document.createElement('input'); field.type = 'hidden'; field.name = 'category_ids[]'; field.value = input.value; field.dataset.publishCategory = input.value; publishForm.append(field);
                } else if (!input.checked) hidden?.remove();
            });
            root.querySelectorAll('[data-publish-tag-source]').forEach((input) => {
                const hidden = root.querySelector(`[data-publish-tag="${input.value}"]`);
                if (input.checked && !hidden) {
                    const field = document.createElement('input'); field.type = 'hidden'; field.name = 'tag_ids[]'; field.value = input.value; field.dataset.publishTag = input.value; publishForm.append(field);
                } else if (!input.checked) hidden?.remove();
            });
            const seoTitle = root.querySelector('[name="seo_title_editor"]')?.value ?? '';
            const seoDescription = root.querySelector('[name="seo_description_editor"]')?.value ?? '';
            publishForm.querySelector('[name="seo_title"]').value = seoTitle;
            publishForm.querySelector('[name="seo_description"]').value = seoDescription;
        });
    }
    editorElement.addEventListener('click', () => {
        if (editor.isEditable) editor.commands.focus();
    });

    previewToggle?.addEventListener('click', () => {
        const previewing = previewToggle.getAttribute('aria-pressed') !== 'true';
        previewToggle.setAttribute('aria-pressed', String(previewing));
        previewToggle.textContent = previewing ? previewToggle.dataset.editLabel : previewToggle.dataset.previewLabel;
        editor.setEditable(!previewing);
        if (toolbar) toolbar.hidden = previewing;
        editor.view.dom.classList.toggle('post-content', previewing);
        status.textContent = previewing ? 'Đang xem trước nội dung bài viết.' : 'Đang chỉnh sửa bài viết.';
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
        alignLeft: () => editor.chain().focus().setTextAlign('left').run(),
        alignCenter: () => editor.chain().focus().setTextAlign('center').run(),
        alignRight: () => editor.chain().focus().setTextAlign('right').run(),
        alignJustify: () => editor.chain().focus().setTextAlign('justify').run(),
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
        // Keep the editor selection when a toolbar control receives the mouse.
        // Without this, the browser moves focus to the button before click and
        // ProseMirror may have no text range to format.
        button.addEventListener('mousedown', (event) => event.preventDefault());
        button.addEventListener('click', () => {
            editor.commands.focus();
            commands[button.dataset.command]?.();
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
        const setImageUploadStatus = (message, loading = false, isError = false) => {
            imageUploadStatus.textContent = message;
            imageUploadStatus.dataset.loading = String(loading);
            imageUploadStatus.classList.toggle('text-red-500', isError);
            imageButton.disabled = loading;
            imageButton.setAttribute('aria-busy', String(loading));
        };
        setImageUploadStatus(imageUploadStatus.dataset.uploadingLabel, true);

        try {
            const response = await fetch(config.mediaUrl, {
                method: 'POST',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                body: form,
            });
            const body = await response.json().catch(() => ({}));

            if (!response.ok) {
                setImageUploadStatus(body.message ?? imageUploadStatus.dataset.failedLabel, false, true);
                return;
            }

            const alt = await window.requestModalInput(
                'Image description',
                'Describe the image (alt text)',
                '',
            );
            if (alt === null) {
                setImageUploadStatus(imageUploadStatus.dataset.notInsertedLabel);
                return;
            }
            // setImage passes all attributes through to the inserted node.
            editor
                .chain()
                .focus()
                .setImage({ src: body.data.url, alt, mediaId: body.data.id })
                .run();
            setImageUploadStatus(imageUploadStatus.dataset.uploadedLabel);
        } catch {
            setImageUploadStatus(imageUploadStatus.dataset.failedLabel, false, true);
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
        console.error('Post editor failed to initialize.', error);

        const loading = root.querySelector('[data-editor-loading]');
        const fallback = root.querySelector('[data-editor-fallback]');
        const status = root.querySelector('[data-save-status]');

        loading?.setAttribute('hidden', '');

        if (fallback) {
            fallback.hidden = false;
        }

        if (status) {
            status.textContent =
                'Trình soạn thảo chưa tải được. Hãy tải lại trang; nếu lỗi vẫn còn, báo quản trị viên.';
            status.classList.add('text-red-500');
        }
    }
});
