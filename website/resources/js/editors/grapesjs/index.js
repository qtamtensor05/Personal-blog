import grapesjs from 'grapesjs';
import 'grapesjs/dist/css/grapes.min.css';

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
