import { test } from 'node:test';
import assert from 'node:assert/strict';
import { Schema } from '@tiptap/pm/model';
import {
    fieldsToApply,
    suggestionDocument,
} from '../../app/Presentation/Js/image-writing-document.js';

const schema = new Schema({
    nodes: {
        doc: { content: 'block+' },
        paragraph: { group: 'block', content: 'text*' },
        heading: { group: 'block', content: 'text*', attrs: { level: { default: 2 } } },
        bulletList: { group: 'block', content: 'listItem+' },
        listItem: { content: 'paragraph+' },
        image: { group: 'block', atom: true, attrs: { mediaId: {}, src: {}, alt: {} } },
        text: {},
    },
});

test('fills only selected fields and preserves fields edited during generation', () => {
    const before = { title: 'Old title', slug: '', seo_title: '' };
    const current = { title: 'New manual title', slug: '', seo_title: '' };
    assert.deepEqual(fieldsToApply(['title', 'slug'], before, current), ['slug']);
});

test('preserves a field edited then restored to its earlier value', () => {
    assert.deepEqual(
        fieldsToApply(['title'], { title: 'Same' }, { title: 'Same' }, { title: 0 }, { title: 2 }),
        [],
    );
});

test('allows explicit replacement of filled fields while leaving unchecked fields alone', () => {
    const before = { title: 'Old title', seo_title: 'Manual SEO' };
    assert.deepEqual(fieldsToApply(['title'], before, before), ['title']);
});

test('creates a rich document with the uploaded image and editable headings and lists', () => {
    const content = {
        type: 'doc',
        content: [
            { type: 'heading', attrs: { level: 2 }, content: [{ type: 'text', text: 'Section' }] },
            { type: 'paragraph', content: [{ type: 'text', text: '<script>alert(1)</script>' }] },
            {
                type: 'bulletList',
                content: [
                    {
                        type: 'listItem',
                        content: [
                            { type: 'paragraph', content: [{ type: 'text', text: 'Detail' }] },
                        ],
                    },
                ],
            },
        ],
    };
    const json = suggestionDocument(content, { id: 30, url: '/storage/image.png' }, 'Photo');
    const doc = schema.nodeFromJSON(json);
    assert.equal(doc.child(0).attrs.mediaId, 30);
    assert.equal(doc.child(1).type.name, 'heading');
    assert.equal(doc.child(2).textContent, '<script>alert(1)</script>');
    assert.equal(doc.child(3).type.name, 'bulletList');
});
