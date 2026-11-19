import { test } from 'node:test';
import assert from 'node:assert/strict';
import { initImageWriting } from '../../app/Presentation/Js/image-writing.js';

class Control {
    value = '';
    checked = false;
    disabled = false;
    hidden = false;
    textContent = '';
    files = [];
    dataset = {};
    listeners = {};
    classList = { toggle() {} };
    addEventListener(name, handler) {
        (this.listeners[name] ??= []).push(handler);
    }
    dispatchEvent(event) {
        (this.listeners[event.type] ?? []).forEach((handler) => handler(event));
        return true;
    }
    async click() {
        await Promise.all(
            (this.listeners.click ?? []).map((handler) => handler(new Event('click'))),
        );
    }
    setAttribute() {}
    removeAttribute() {}
}

function harness() {
    const controls = Object.fromEntries(
        [
            'data-ai-file',
            'data-ai-preview',
            'data-ai-generate',
            'data-ai-status',
            'data-ai-alt',
            'data-ai-language',
            'data-ai-instructions',
        ].map((key) => [`[${key}]`, new Control()]),
    );
    const title = new Control();
    title.value = 'Manual title';
    const slug = new Control();
    slug.value = 'manual-title';
    const excerpt = new Control();
    const seoTitle = new Control();
    const seoDescription = new Control();
    const choices = [
        'title',
        'slug',
        'excerpt',
        'seo_title',
        'seo_description',
        'image_alt',
        'content',
    ].map((key) => {
        const choice = new Control();
        choice.value = key;
        choice.dataset.label = key;
        return choice;
    });
    const panel = {
        querySelector: (selector) => controls[selector] ?? null,
        querySelectorAll: () => choices,
    };
    const root = {
        querySelector: (selector) =>
            ({
                '[data-image-writing]': panel,
                '[data-publish-slug-source]': slug,
                '[data-post-excerpt-source]': excerpt,
                '[name="seo_title_editor"]': seoTitle,
                '[name="seo_description_editor"]': seoDescription,
            })[selector] ?? null,
    };
    let doc = { type: 'doc', content: [{ type: 'paragraph' }] };
    const listeners = {};
    const editor = {
        isEditable: true,
        isEmpty: true,
        isDestroyed: false,
        state: { selection: { from: 1 }, doc: { content: { size: 2 }, textBetween: () => '' } },
        getJSON: () => doc,
        on: (name, handler) => (listeners[name] ??= []).push(handler),
        commands: {
            setContent(content) {
                doc = content;
                (listeners.update ?? []).forEach((handler) => handler());
                (listeners.transaction ?? []).forEach((handler) => handler());
                return true;
            },
        },
    };
    const labels = Object.fromEntries(
        [
            'generating',
            'throttled',
            'failed',
            'filled',
            'skipped',
            'selected',
            'uploading',
            'unsupported',
            'tooLarge',
            'choose_fields',
        ].map((key) => [key, key]),
    );
    const config = {
        blogId: 10,
        mediaUrl: '/media',
        imageWriting: { available: true, url: '/ai', labels },
    };
    initImageWriting(root, editor, config, 'csrf', title);
    const input = controls['[data-ai-file]'];
    input.files = [new File(['image-bytes'], 'photo.png', { type: 'image/png' })];
    input.dispatchEvent(new Event('change'));
    return {
        controls,
        choices,
        title,
        slug,
        excerpt,
        seoTitle,
        seoDescription,
        editor,
        generate: controls['[data-ai-generate]'],
        status: controls['[data-ai-status]'],
    };
}

const response = () => ({
    title: 'AI title',
    slug: 'ai-title',
    excerpt: 'AI summary',
    seo_title: 'AI SEO title',
    seo_description: 'AI SEO description',
    image_alt: 'AI alt',
    content: {
        type: 'doc',
        content: [
            { type: 'heading', attrs: { level: 2 }, content: [{ type: 'text', text: 'Section' }] },
        ],
    },
});

async function withFetch(run, duringGeneration = () => {}) {
    const original = globalThis.fetch;
    const calls = [];
    globalThis.fetch = async (url, options) => {
        calls.push({ url, options });
        if (url === '/media')
            return {
                ok: true,
                json: async () => ({ data: { id: 30, url: '/storage/photo.png' } }),
            };
        duringGeneration();
        return { ok: true, json: async () => ({ data: response() }) };
    };
    try {
        await run(calls);
    } finally {
        globalThis.fetch = original;
    }
}

test('uploads a device image and fills the selected real post fields directly', async () => {
    const h = harness();
    h.choices.forEach((choice) => {
        choice.checked = true;
    });
    await withFetch(async (calls) => {
        await h.generate.click();
        assert.equal(calls.length, 2);
        assert.equal(calls[0].options.body.get('blog_id'), '10');
        assert.equal(JSON.parse(calls[1].options.body).media_id, 30);
        assert.equal(h.title.value, 'AI title');
        assert.equal(h.slug.value, 'ai-title');
        assert.equal(h.excerpt.value, 'AI summary');
        assert.equal(h.seoTitle.value, 'AI SEO title');
        assert.equal(h.seoDescription.value, 'AI SEO description');
        assert.equal(h.editor.getJSON().content[0].attrs.mediaId, 30);
        assert.equal(h.editor.getJSON().content[1].type, 'heading');
    });
    assert.match(h.status.textContent, /filled/);
});

test('keeps manual edits made while Gemini is working but fills unchanged fields', async () => {
    const h = harness();
    h.choices.forEach((choice) => {
        choice.checked = true;
    });
    await withFetch(
        async () => {
            await h.generate.click();
        },
        () => {
            h.title.value = 'New manual title';
            h.title.dispatchEvent(new Event('input'));
        },
    );
    assert.equal(h.title.value, 'New manual title');
    assert.equal(h.seoTitle.value, 'AI SEO title');
    assert.match(h.status.textContent, /skipped title/);
});

test('changing the title through AI does not alter an unchecked slug', async () => {
    const h = harness();
    h.choices.forEach((choice) => {
        choice.checked = choice.value === 'title';
    });
    let slugEdited = false;
    h.slug.addEventListener('input', () => {
        slugEdited = true;
    });
    h.title.addEventListener('input', () => {
        if (!slugEdited) h.slug.value = 'unwanted-auto-slug';
    });
    await withFetch(async () => {
        await h.generate.click();
    });
    assert.equal(h.title.value, 'AI title');
    assert.equal(h.slug.value, 'manual-title');
    assert.equal(h.seoTitle.value, '');
});

test('requires selecting a field before uploading or requesting AI', async () => {
    const h = harness();
    h.choices.forEach((choice) => {
        choice.checked = false;
    });
    await withFetch(async (calls) => {
        await h.generate.click();
        assert.equal(calls.length, 0);
    });
    assert.equal(h.status.textContent, 'choose_fields');
});

test('a provider timeout preserves all existing form fields and article content', async () => {
    const h = harness();
    h.choices.forEach((choice) => {
        choice.checked = true;
    });
    const previousFetch = globalThis.fetch;
    const previousWindow = globalThis.window;
    const originalContent = h.editor.getJSON();
    let shownMessage = '';
    globalThis.window = {
        showErrorModal(message) {
            shownMessage = message;
        },
    };
    globalThis.fetch = async (url) =>
        url === '/media'
            ? { ok: true, json: async () => ({ data: { id: 30, url: '/storage/photo.png' } }) }
            : {
                  ok: false,
                  status: 503,
                  json: async () => ({ message: 'AI took too long to respond.' }),
              };
    try {
        await h.generate.click();
        assert.equal(h.title.value, 'Manual title');
        assert.equal(h.slug.value, 'manual-title');
        assert.equal(h.seoTitle.value, '');
        assert.deepEqual(h.editor.getJSON(), originalContent);
        assert.equal(shownMessage, 'AI took too long to respond.');
        assert.equal(h.generate.disabled, false);
    } finally {
        globalThis.fetch = previousFetch;
        globalThis.window = previousWindow;
    }
});

test('sends selected fields and accepts a response containing only those fields', async () => {
    const h = harness();
    h.choices.forEach((choice) => {
        choice.checked = choice.value === 'seo_title';
    });
    const previousFetch = globalThis.fetch;
    globalThis.fetch = async (url, options) => {
        if (url === '/media')
            return {
                ok: true,
                json: async () => ({ data: { id: 30, url: '/storage/photo.png' } }),
            };
        assert.deepEqual(JSON.parse(options.body).fields, ['seo_title']);
        return { ok: true, json: async () => ({ data: { seo_title: 'Only requested SEO' } }) };
    };
    try {
        await h.generate.click();
        assert.equal(h.seoTitle.value, 'Only requested SEO');
        assert.equal(h.title.value, 'Manual title');
        assert.equal(h.seoDescription.value, '');
    } finally {
        globalThis.fetch = previousFetch;
    }
});
