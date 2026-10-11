import grapesjs from 'grapesjs';
import 'grapesjs/dist/css/grapes.min.css';

export function mountConstrainedDesignEditor(root, project, onChange) {
    const sections = project.pages?.[0]?.sections ?? [
        {
            type: 'hero',
            content: 'Một câu chuyện riêng',
            description: 'Chào mừng đến blog của bạn.',
        },
        { type: 'post-list', content: 'Bài viết mới' },
    ];
    const editor = grapesjs.init({
        container: root.querySelector('[data-grapes]'),
        height: '560px',
        storageManager: false,
        allowScripts: false,
        fromElement: false,
        selectorManager: { componentFirst: true },
        styleManager: { sectors: [] },
        assetManager: { assets: [], upload: false },
        deviceManager: {
            devices: [
                { name: 'Desktop', width: '' },
                { name: 'Tablet', width: '768px' },
                { name: 'Mobile', width: '360px' },
            ],
        },
        plugins: [
            (instance) => {
                ['hero', 'text', 'post-list', 'header', 'footer'].forEach((type) => {
                    instance.DomComponents.addType(type, {
                        model: {
                            defaults: {
                                tagName: 'section',
                                content:
                                    type === 'post-list'
                                        ? 'Bài viết mới (dữ liệu động)'
                                        : 'Nội dung',
                                description: '',
                                droppable: false,
                                editable: false,
                                stylable: false,
                                attributes: {},
                                traits: [
                                    {
                                        name: 'content',
                                        label: 'Tiêu đề / nội dung',
                                        changeProp: true,
                                    },
                                    { name: 'description', label: 'Mô tả', changeProp: true },
                                ],
                            },
                        },
                        view: {
                            init() {
                                this.listenTo(
                                    this.model,
                                    'change:content change:description',
                                    this.render,
                                );
                            },
                            onRender() {
                                this.el.replaceChildren();
                                const h = document.createElement('h2');
                                h.textContent = this.model.get('content');
                                const p = document.createElement('p');
                                p.textContent = this.model.get('description');
                                this.el.append(h, p);
                                this.el.style.cssText =
                                    'padding:32px;border:1px solid #dce3e8;background:#fff;font-family:system-ui;color:#172b35;';
                            },
                        },
                    });
                    instance.BlockManager.add(type, {
                        label: {
                            hero: 'Hero',
                            text: 'Văn bản',
                            'post-list': 'Bài viết động',
                            header: 'Header',
                            footer: 'Footer',
                        }[type],
                        content: { type },
                    });
                });
            },
        ],
    });
    editor.setComponents(
        sections.map((section) => ({
            type: section.type,
            content: section.content ?? '',
            description: section.description ?? '',
        })),
    );
    editor.on('update', () => onChange(editor.getProjectData()));
    root.querySelectorAll('[data-device]').forEach((button) =>
        button.addEventListener('click', () => editor.setDevice(button.dataset.device)),
    );
    return () => editor.destroy();
}

export function mountDesignEditor(element, initialData, onChange) {
    const editor = grapesjs.init({
        container: element,
        height: '70vh',
        storageManager: false,
        projectData: initialData?.project,
        blockManager: {
            blocks: [
                {
                    id: 'hero',
                    label: 'Mở đầu',
                    content:
                        '<section data-gjs-type="hero"><h1>Tiêu đề</h1><p>Lời giới thiệu</p></section>',
                },
                {
                    id: 'text',
                    label: 'Văn bản',
                    content: '<section data-gjs-type="text"><p>Nội dung của bạn</p></section>',
                },
                {
                    id: 'image',
                    label: 'Hình ảnh',
                    content: '<figure data-gjs-type="image"><img alt="" /></figure>',
                },
                {
                    id: 'post-list',
                    label: 'Danh sách bài',
                    content: '<section data-gjs-type="post-list">Bài viết mới</section>',
                },
            ],
        },
    });
    let timer;
    editor.on('update', () => {
        clearTimeout(timer);
        timer = setTimeout(
            () => onChange({ ...initialData, project: editor.getProjectData() }),
            700,
        );
    });
    return () => {
        clearTimeout(timer);
        editor.destroy();
    };
}
