import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';

// Exercise the actual Studio upload script with a delayed transport.
const view = readFileSync(new URL('../../app/Presentation/Views/blogs/design.blade.php', import.meta.url), 'utf8');
const script = view.slice(view.indexOf('async function upload('), view.indexOf("document.querySelectorAll('[data-direct-media]').forEach"));

function harness(fetch) {
    let submit;
    const buttons = [0, 1].map(() => ({ disabled: false, setAttribute() {} }));
    const root = { dataset: {}, querySelector: () => status };
    const form = {
        querySelector: (selector) => {
            const state = root.dataset.uploadState;
            return state === 'pending' || (selector.includes('error') && state === 'error') ? root : null;
        },
        querySelectorAll: () => buttons,
        addEventListener: (_, callback) => { submit = callback; },
    };
    const status = {
        closest: (selector) => selector === 'form' ? form : root,
        classList: { remove() {} },
    };
    const context = vm.createContext({
        fetch, FormData: class { append() {} },
        document: { querySelectorAll: () => [form] }, window: {},
        blogId: 1, csrf: 'test', uploadUrl: '/media', uploadText: '__FILE__',
    });
    vm.runInContext(script, context);
    return {
        upload: () => context.upload({ name: 'logo.jpg' }, status),
        buttons,
        canSubmit: () => {
            let allowed = true;
            submit({ preventDefault() { allowed = false; } });
            return allowed;
        },
    };
}

test('blocks saving until logo upload finishes, then allows saving', async () => {
    let resolve;
    const h = harness(() => new Promise((done) => { resolve = done; }));
    const pending = h.upload();
    assert.ok(h.buttons.every((button) => button.disabled));
    assert.equal(h.canSubmit(), false);
    resolve({ ok: true, json: async () => ({ data: { id: 7, url: '/logo.jpg' } }) });
    assert.equal((await pending).id, 7);
    assert.ok(h.buttons.every((button) => !button.disabled));
    assert.equal(h.canSubmit(), true);
});

test('failed or incomplete logo upload cannot silently save without an image', async () => {
    for (const result of [{ ok: false, data: {} }, { ok: true, data: {} }]) {
        const h = harness(async () => ({ ok: result.ok, json: async () => ({ data: result.data }) }));
        await assert.rejects(h.upload());
        assert.equal(h.canSubmit(), false);
        assert.ok(h.buttons.every((button) => !button.disabled));
    }
});
