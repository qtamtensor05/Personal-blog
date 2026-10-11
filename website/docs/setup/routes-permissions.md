# Routes, panels và quyền

| Khu vực | URL | Điều kiện |
|---|---|---|
| Main website | `/`, `/blogs`, `/templates` | Public; chỉ đọc blog/template public |
| Blog public | `/b/{blogSlug}` | Blog PUBLISHED, PUBLIC hoặc UNLISTED |
| Nội dung public | `/b/{blogSlug}/posts/{slug}`, `/pages/{slug}` | Cùng `blog_id`, nội dung PUBLISHED |
| Workspace owner | `/workspace` | Session + email verified + account ACTIVE; query theo owner |
| Tài khoản | `/account`, POST `/account/{profile,email,password}` | Session + verified + ACTIVE; chỉ chính mình; đổi credential cần mật khẩu hiện tại |
| Vai trò/quản trị viên | `/platform-admin/access-management` | Platform session + 2FA theo panel; đọc theo roles.manage/admins.manage; mutation kiểm tra quyền riêng phía server |
| Blog panel | `/blog-admin` | Session + email verified + account ACTIVE |
| Platform panel | `/platform-admin` | Như trên + `platform.dashboard.view` còn hiệu lực |
| Preview bài/trang | `/workspace/blogs/{blog}/preview/{kind}/{id}` | Auth + verified + active + đúng Owner; noindex/no-store |
| Preview thiết kế | `/workspace/blogs/{blog}/preview-design` | Auth + verified + active + đúng Owner; noindex/no-store |

SUPER_ADMIN nhận toàn bộ permission seed. PLATFORM_ADMIN không có `platform.roles.manage`, `platform.admins.manage`, `platform.security.manage`, `platform.settings.manage`, `platform.users.delete`. Owner không nhận platform role; quyền đến từ `blogs.owner_user_id`.

Public query không dùng `blog_id` từ form làm tenant authority. Mọi post/page query ràng buộc đồng thời slug blog và `blog_id`; private, hidden, suspended, archived và draft không xuất hiện trên public/discovery.
