let messages;

export function translate(key) {
    messages ??= JSON.parse(
        document.querySelector('[data-editor-messages]')?.textContent ?? '{}',
    );
    return messages[key] ?? key;
}
