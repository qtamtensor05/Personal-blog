window.ButMayEditors = {
    async design(root, wire, project) {
        const { mountConstrainedDesignEditor } = await import('./editors/grapesjs');
        if (!root.isConnected) return;
        const destroy = mountConstrainedDesignEditor(root, project, (value) => {
            wire.set('form.project', value, false);
            const hero = value.pages?.[0]?.frames?.[0]?.component?.components?.find(
                (section) => section.type === 'hero',
            );
            if (hero) {
                wire.set('form.heading', hero.content ?? '', false);
                wire.set('form.description', hero.description ?? '', false);
            }
            root.closest('[data-dirty-guard]')?.setAttribute('data-dirty', 'true');
        });
        const observer = new MutationObserver(() => {
            if (!root.isConnected) {
                observer.disconnect();
                destroy();
            }
        });
        observer.observe(document.body, { childList: true, subtree: true });
    },
    async content(root, wire, content) {
        const [{ Editor }, { default: StarterKit }, { default: Image }] = await Promise.all([
            import('@tiptap/core'),
            import('@tiptap/starter-kit'),
            import('@tiptap/extension-image'),
        ]);
        const MediaImage = Image.extend({
            addAttributes() {
                return { ...this.parent?.(), mediaId: { default: null } };
            },
        });
        const editor = new Editor({
            element: root.querySelector('[data-tiptap]'),
            content,
            extensions: [StarterKit, MediaImage],
            onUpdate: ({ editor: instance }) =>
                wire.set('form.content_json', instance.getJSON(), false),
        });
        root.querySelectorAll('[data-command]').forEach((button) =>
            button.addEventListener('click', () => {
                const command = button.dataset.command;
                const chain = editor.chain().focus();
                if (command === 'heading') chain.toggleHeading({ level: 2 }).run();
                else if (command === 'bold') chain.toggleBold().run();
                else if (command === 'italic') chain.toggleItalic().run();
                else if (command === 'bulletList') chain.toggleBulletList().run();
                else if (command === 'undo') chain.undo().run();
            }),
        );
        root.querySelector('[data-media]')?.addEventListener('change', (event) => {
            const option = event.target.selectedOptions[0];
            if (option.value)
                editor
                    .chain()
                    .focus()
                    .setImage({
                        src: option.dataset.src,
                        alt: option.dataset.alt,
                        mediaId: Number(option.value),
                    })
                    .run();
            event.target.value = '';
        });
    },
};
window.dispatchEvent(new Event('butmay-editors-ready'));

document.addEventListener('click', (event) => {
    const link = event.target.closest('a[href]');
    if (
        link &&
        document.querySelector('[data-dirty-guard][data-dirty="true"]') &&
        !window.confirm('Rời editor? Thay đổi chưa lưu sẽ mất.')
    ) {
        event.preventDefault();
        return;
    }
    const button = event.target.closest('[data-toggle-password]');
    if (!button) return;
    const input = button.closest('form').querySelector('[name=password]');
    input.type = input.type === 'password' ? 'text' : 'password';
    button.textContent = input.type === 'password' ? 'Hiện mật khẩu' : 'Ẩn mật khẩu';
});
document.addEventListener('input', (event) =>
    event.target.closest('[data-dirty-guard]')?.setAttribute('data-dirty', 'true'),
);
window.addEventListener('editor-saved', () =>
    document.querySelectorAll('[data-dirty-guard]').forEach((root) => delete root.dataset.dirty),
);
window.addEventListener('beforeunload', (event) => {
    if (document.querySelector('[data-dirty-guard][data-dirty="true"]')) {
        event.preventDefault();
        event.returnValue = '';
    }
});
document.addEventListener('change', (event) => {
    if (event.target.matches('[data-viewport]')) {
        document.querySelector('[data-preview-stage]').style.width = event.target.value;
    }
});
document.addEventListener('submit', (event) => {
    if (event.target.hasAttribute('wire:submit')) return;
    event.target.querySelectorAll('button[type=submit],button:not([type])').forEach((button) => {
        button.disabled = true;
    });
});
