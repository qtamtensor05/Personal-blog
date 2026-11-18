export function fieldsToApply(selected, before, current, revisionsBefore = {}, revisionsNow = {}) {
    return selected.filter(
        (field) =>
            Object.hasOwn(before, field) &&
            before[field] === current[field] &&
            revisionsBefore[field] === revisionsNow[field],
    );
}

export function suggestionDocument(content, media, alt) {
    return {
        type: 'doc',
        content: [
            { type: 'image', attrs: { mediaId: media.id, src: media.url, alt } },
            ...content.content,
        ],
    };
}
