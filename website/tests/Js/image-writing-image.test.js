import { test } from 'node:test';
import assert from 'node:assert/strict';
import { prepareAnalysisImage } from '../../app/Presentation/Js/image-writing-image.js';

test('makes a bounded AI-only JPEG without altering the original file', async () => {
    const originalBitmap = globalThis.createImageBitmap;
    const originalDocument = globalThis.document;
    let closed = false;
    let canvas;
    globalThis.createImageBitmap = async () => ({
        width: 4000,
        height: 3000,
        close() {
            closed = true;
        },
    });
    globalThis.document = {
        createElement() {
            canvas = {
                getContext: () => ({ fillRect() {}, drawImage() {} }),
                toBlob(resolve) {
                    resolve(new Blob(['optimized'], { type: 'image/jpeg' }));
                },
            };
            return canvas;
        },
    };
    const original = new File([new Uint8Array(512 * 1024)], 'original.png', { type: 'image/png' });
    try {
        const copy = await prepareAnalysisImage(original);
        assert.equal(canvas.width, 1024);
        assert.equal(canvas.height, 768);
        assert.equal(copy.type, 'image/jpeg');
        assert.ok(copy.size < original.size);
        assert.equal(original.type, 'image/png');
        assert.equal(original.size, 512 * 1024);
        assert.equal(closed, true);
    } finally {
        globalThis.createImageBitmap = originalBitmap;
        globalThis.document = originalDocument;
    }
});

test('skips already small images instead of recompressing them', async () => {
    assert.equal(
        await prepareAnalysisImage(new File(['small'], 'small.png', { type: 'image/png' })),
        null,
    );
});

test('falls back safely when browser image decoding fails', async () => {
    const originalBitmap = globalThis.createImageBitmap;
    globalThis.createImageBitmap = async () => {
        throw new Error('unsupported');
    };
    try {
        const image = new File([new Uint8Array(512 * 1024)], 'photo.png', { type: 'image/png' });
        assert.equal(await prepareAnalysisImage(image), null);
    } finally {
        globalThis.createImageBitmap = originalBitmap;
    }
});
