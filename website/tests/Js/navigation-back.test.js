import { test } from 'node:test';
import assert from 'node:assert/strict';
import { recordNavigation, initNavigationBack } from '../../app/Presentation/Js/navigation-back.js';

const base = 'https://blog.test';
test('saving, reloading and switching design regions retain the previous page', () => {
    let stack = recordNavigation([], `${base}/my-blogs`);
    stack = recordNavigation(stack, `${base}/blogs/1`);
    stack = recordNavigation(stack, `${base}/blogs/1/design?region=body`);
    stack = recordNavigation(stack, `${base}/blogs/1/design?region=header`);
    stack = recordNavigation(stack, `${base}/blogs/1/design?region=header`);
    assert.deepEqual(stack, [`${base}/my-blogs`, `${base}/blogs/1`, `${base}/blogs/1/design?region=header`]);
    stack = recordNavigation(stack, `${base}/blogs/1`);
    assert.deepEqual(stack, [`${base}/my-blogs`, `${base}/blogs/1`]);
});

test('preserves list filters and ignores external referrers and corrupt storage', () => {
    assert.deepEqual(recordNavigation(null, `${base}/blogs/1`, 'https://other.test/'), [`${base}/blogs/1`]);
    assert.deepEqual(recordNavigation(['javascript:alert(1)'], `${base}/blogs/1`, `${base}/my-blogs?page=2`),
        [`${base}/my-blogs?page=2`, `${base}/blogs/1`]);
});

test('updates the back link after save and retains fallback when opened directly', () => {
    const link = { href: `${base}/my-blogs` };
    let stored = JSON.stringify([`${base}/my-blogs?page=2`, `${base}/blogs/1`]);
    const listeners = {};
    const win = {
        location: { href: `${base}/blogs/1/edit` },
        sessionStorage: { getItem: () => stored, setItem: (_, value) => { stored = value; } },
        addEventListener: (event, handler) => { listeners[event] = handler; },
    };
    const doc = { referrer: `${base}/blogs/1/edit`, querySelectorAll: () => [link] };
    initNavigationBack(win, doc);
    listeners.pageshow();
    assert.equal(link.href, `${base}/blogs/1`);
    stored = 'broken';
    link.href = `${base}/my-blogs`;
    listeners.pageshow();
    assert.equal(link.href, `${base}/my-blogs`);
});
