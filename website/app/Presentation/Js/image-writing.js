import { prepareAnalysisImage } from './image-writing-image.js';
import { fieldsToApply, suggestionDocument } from './image-writing-document.js';

export function initImageWriting(root, editor, config, csrf, titleInput) {
    const panel = root.querySelector('[data-image-writing]');
    if (!panel) return null;

    const labels = config.imageWriting.labels;
    const fileInput = panel.querySelector('[data-ai-file]');
    const preview = panel.querySelector('[data-ai-preview]');
    const generate = panel.querySelector('[data-ai-generate]');
    const fieldInputs = {
        title: titleInput,
        slug: root.querySelector('[data-publish-slug-source]'),
        excerpt: root.querySelector('[data-post-excerpt-source]'),
        seo_title: root.querySelector('[name="seo_title_editor"]'),
        seo_description: root.querySelector('[name="seo_description_editor"]'),
        image_alt: panel.querySelector('[data-ai-alt]'),
    };
    const choices = [...panel.querySelectorAll('[data-ai-field]')];
    const values = () => ({
        ...Object.fromEntries(
            Object.entries(fieldInputs).map(([key, input]) => [key, input.value]),
        ),
        content: JSON.stringify(editor.getJSON()),
    });
    choices.forEach((choice) => {
        choice.checked =
            choice.value === 'content' ? editor.isEmpty : !fieldInputs[choice.value].value.trim();
    });
    const status = panel.querySelector('[data-ai-status]');
    const altInput = panel.querySelector('[data-ai-alt]');
    const revisions = Object.fromEntries(
        [...Object.keys(fieldInputs), 'content'].map((key) => [key, 0]),
    );
    Object.entries(fieldInputs).forEach(([key, input]) =>
        input.addEventListener('input', () => {
            revisions[key]++;
        }),
    );
    editor.on('update', () => {
        revisions.content++;
    });
    let file = null;
    let media = null;
    let analysisImage = null;
    let analysisPrepared = false;
    let previewUrl = null;
    let busy = false;
    let controller = null;

    const setStatus = (message, error = false) => {
        status.textContent = message;
        status.classList.toggle('text-red-500', error);
    };
    const refresh = () => {
        fileInput.disabled = busy || !editor.isEditable;
        panel.querySelector('[data-ai-language]').disabled = busy;
        panel.querySelector('[data-ai-instructions]').disabled = busy;
        generate.disabled = busy || !file || !config.imageWriting.available || !editor.isEditable;
        generate.setAttribute('aria-busy', String(busy));
        choices.forEach((choice) => {
            choice.disabled = busy;
        });
    };
    const clearFile = (resetInput = true) => {
        if (previewUrl) URL.revokeObjectURL(previewUrl);
        previewUrl = null;
        file = null;
        media = null;
        analysisImage = null;
        analysisPrepared = false;
        preview.hidden = true;
        preview.removeAttribute('src');
        if (resetInput) fileInput.value = '';
    };
    fileInput.addEventListener('change', () => {
        const selected = fileInput.files[0];
        if (!selected) return;
        clearFile(false);
        if (!['image/jpeg', 'image/png', 'image/webp'].includes(selected.type)) {
            setStatus(labels.unsupported, true);
        } else if (selected.size > 5 * 1024 * 1024) {
            setStatus(labels.tooLarge, true);
        } else {
            file = selected;
            previewUrl = URL.createObjectURL(file);
            preview.src = previewUrl;
            preview.hidden = false;
            setStatus(labels.selected);
        }
        refresh();
    });
    editor.on('transaction', refresh);

    generate.addEventListener('click', async () => {
        if (busy || !file || !editor.isEditable) return;
        const selectedFields = choices
            .filter((choice) => choice.checked)
            .map((choice) => choice.value);
        if (!selectedFields.length) {
            setStatus(labels.choose_fields, true);
            return;
        }
        const before = values();
        const revisionsBefore = { ...revisions };
        busy = true;
        refresh();
        controller = new AbortController();
        let timeout = setTimeout(() => controller.abort(), 70000);
        try {
            if (!analysisPrepared) {
                setStatus(labels.preparing);
                analysisImage = await prepareAnalysisImage(file);
                analysisPrepared = true;
            }
            if (!media) {
                setStatus(labels.uploading);
                const form = new FormData();
                form.append('file', file);
                form.append('scope', 'BLOG');
                form.append('blog_id', config.blogId);
                form.append('alt_text', altInput.value);
                const upload = await fetch(config.mediaUrl, {
                    method: 'POST',
                    headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                    signal: controller.signal,
                    body: form,
                });
                const body = await upload.json().catch(() => ({}));
                if (!upload.ok || !body.data?.id || !body.data?.url) {
                    throw new Error(
                        Object.values(body.errors ?? {})[0]?.[0] || body.message || labels.failed,
                    );
                }
                media = body.data;
            }
            clearTimeout(timeout);
            timeout = setTimeout(() => controller.abort(), 75000);
            setStatus(labels.generating);
            const context = editor.state.doc
                .textBetween(
                    Math.max(0, editor.state.selection.from - 1500),
                    Math.min(editor.state.doc.content.size, editor.state.selection.from + 1500),
                    '\n',
                )
                .slice(0, 4000);
            const payload = {
                media_id: media.id,
                title: titleInput.value,
                context,
                instructions: panel.querySelector('[data-ai-instructions]').value,
                locale: panel.querySelector('[data-ai-language]').value,
                fields: selectedFields,
            };
            const headers = { Accept: 'application/json', 'X-CSRF-TOKEN': csrf };
            let requestBody;
            if (analysisImage) {
                requestBody = new FormData();
                Object.entries(payload).forEach(([key, value]) => {
                    if (key === 'fields')
                        value.forEach((field) => requestBody.append('fields[]', field));
                    else requestBody.append(key, value);
                });
                requestBody.append('analysis_image', analysisImage, 'analysis.jpg');
            } else {
                headers['Content-Type'] = 'application/json';
                requestBody = JSON.stringify(payload);
            }
            const response = await fetch(config.imageWriting.url, {
                method: 'POST',
                headers,
                signal: controller.signal,
                body: requestBody,
            });
            const body = await response.json().catch(() => ({}));
            if (!response.ok) {
                throw new Error(
                    response.status === 429
                        ? labels.throttled
                        : Object.values(body.errors ?? {})[0]?.[0] || body.message || labels.failed,
                );
            }
            const data = body.data;
            if (
                !data ||
                selectedFields.some((key) =>
                    key === 'content'
                        ? data.content?.type !== 'doc' || !Array.isArray(data.content.content)
                        : typeof data[key] !== 'string',
                )
            ) {
                throw new Error(labels.failed);
            }
            if (!editor.isEditable) throw new Error(labels.failed);
            const current = values();
            const applicable = fieldsToApply(
                selectedFields,
                before,
                current,
                revisionsBefore,
                revisions,
            );
            const skipped = selectedFields.filter((key) => !applicable.includes(key));
            const applied = [];
            // Set values before dispatching input events so autosave sees a consistent form.
            applicable
                .filter((key) => key !== 'content')
                .forEach((key) => {
                    fieldInputs[key].value = data[key];
                    applied.push(key);
                });
            // Mark the existing slug as edited before the title event, preventing auto-slug side effects.
            if (applied.includes('title'))
                fieldInputs.slug.dispatchEvent(new Event('input', { bubbles: true }));
            applied.forEach((key) =>
                fieldInputs[key].dispatchEvent(new Event('input', { bubbles: true })),
            );
            if (applicable.includes('content')) {
                const alt = fieldInputs.image_alt.value || data.image_alt || '';
                const appliedContent = editor.commands.setContent(
                    suggestionDocument(data.content, media, alt),
                );
                if (appliedContent) applied.push('content');
                else skipped.push('content');
            }
            const names = (keys) =>
                choices
                    .filter((choice) => keys.includes(choice.value))
                    .map((choice) => choice.dataset.label)
                    .join(', ');
            setStatus(
                (applied.length ? labels.filled + ' ' + names(applied) + '.' : '') +
                    (skipped.length ? ' ' + labels.skipped + ' ' + names(skipped) + '.' : ''),
            );
            if (applied.includes('content')) clearFile();
        } catch (error) {
            if (editor.isDestroyed) return;
            const message =
                error.name === 'TypeError' || error.name === 'AbortError'
                    ? labels.failed
                    : error.message;
            setStatus(message, true);
            window.showErrorModal?.(message);
        } finally {
            clearTimeout(timeout);
            busy = false;
            controller = null;
            if (!editor.isDestroyed) refresh();
        }
    });

    editor.on('destroy', () => {
        controller?.abort();
        if (previewUrl) URL.revokeObjectURL(previewUrl);
    });
    refresh();
    return { refresh };
}
