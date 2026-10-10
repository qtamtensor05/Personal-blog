# JSON contracts v1

Template gồm `schemaVersion`, GrapesJS `project`, `themeDefaults`, `themeSchema`, `sectionRules`, `pageBindings`, `navigation`. Design snapshot thêm `sourceVersion`, `theme` và `customizationRules`. Server sở hữu version/rules; payload client không được mở rộng allowlist.

Tiptap document phải có root `{ "type": "doc", "content": [] }`. Renderer chỉ nhận text, paragraph, heading, list, blockquote, hardBreak, image có `mediaId`, và table. Text luôn escape; URL chỉ nhận HTTP(S), path nội bộ, anchor hoặc mailto; node lạ bị từ chối.

Fingerprint là SHA-256 của JSON đã sắp khóa đệ quy. Publish so sánh fingerprint trong cùng transaction với row lock để phát hiện editor/tab cũ.
