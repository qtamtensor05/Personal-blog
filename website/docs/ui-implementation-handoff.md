# Bàn giao UI — 2026-10-11

## Nguồn và giả định

Đã đọc đặc tả `DESIGN_REQUIREMENTS_BLOG_PLATFORM.md`, kiến trúc modular monolith và tài liệu/schema canonical trong `docs/database/`. Không tìm thấy AGENTS.md ở repository và các thư mục cha đã kiểm tra. Giữ Laravel 13 / Filament 5 / Livewire 4 / Blade / GrapesJS / Tiptap / Query Builder và schema hiện có, không thêm migration nghiệp vụ.

Hai ảnh là tham chiếu bố cục/màu. Tên hiển thị lấy từ branding trong platform settings; tài liệu/repo dùng Bút Mây. Không sao chép số liệu hoặc nhãn phổ biến từ mockup. Thumbnail được render từ JSON mẫu; `public/images/journal.svg` và `mountains.svg` là minh họa vector nguyên gốc do dự án tự tạo, không phụ thuộc ảnh bên thứ ba.

Yêu cầu tiếp tục gần nhất tập trung vào hai UI: website chọn mẫu/auth và Platform Admin quản lý mẫu. Blog Admin cùng renderer được nối nền để luồng tạo blog không kết thúc ở màn hình giả; không coi đây là nghiệm thu toàn bộ sáu lát cắt/P1.

## Hai UI chính

- Gallery: design tokens sáng/teal; desktop hero + login, mobile login riêng; gallery 3/2/1 cột, tìm tên/mô tả, lọc chủ đề và phân trang giữ URL; empty state; nhãn/input/focus và skip link.
- Preview: dựng trực tiếp từ JSON, đổi viewport, quay lại thư viện và Dùng mẫu. Không lấy demo_url không được kiểm tra để chuyển người dùng sang website tùy ý.
- Auth: login/register/verify/password reset riêng; hiện/ẩn mật khẩu; throttle; reset không tiết lộ tài khoản có tồn tại. Lựa chọn mẫu trong session phía server, kiểm tra lại khi tạo blog.
- Tạo blog: transaction/quota/owner lock, kiểm tra mẫu PUBLISHED, lưu snapshot vào cặp draft của blog_designs. Không đổi live khi mẫu nguồn được cập nhật.
- Platform Admin: sidebar Filament 232px, responsive drawer, gallery template, số đếm database, tìm/lọc trạng thái/chủ đề, phân trang; tạo/sửa, giữ chủ đề khi sửa, lưu nháp/xuất bản/ngừng cho chọn; preview draft ngay trong editor.
- Editor mẫu: form màu/bố cục/hero/footer, GrapesJS section allowlist, thiết bị desktop/tablet/mobile, thêm/sắp xếp section và traits. Lưu nguyên native project kèm projection section được validate. Không có HTML/JS tùy ý hoặc lịch sử phiên bản. Phát hiện fingerprint cũ, giữ form khi validation lỗi và cảnh báo rời editor chưa lưu.
- Platform session phải đi qua login của Filament; phiên portal không được bỏ qua bước challenge 2FA. Quyền được kiểm tra trong Query/Action, không chỉ menu.

## Nền hỗ trợ đã nối

- OwnerContext là bộ resolve/policy duy nhất theo owner_user_id, mọi query/mutation tiếp tục scope blog_id.
- Studio: bài/trang, Tiptap, cover/SEO, phân loại, draft/live, publish/schedule/archive, protected preview; media upload/alt/delete có kiểm tra tham chiếu; cài đặt blog và draft design/menu.
- Public: homepage từ published_data, bài/trang live, search/category/tag, menu trang published, header/footer nhất quán, private chỉ Owner đã xác minh.
- Platform settings cập nhật từng nhóm JSON; public chỉ nhận projection allowlist; moderation ghi audit và hạn chế tài khoản thường, không tự khóa admin.
- Editor tải lazy để gallery không phải tải GrapesJS/Tiptap; Cloudinary SDK được nối qua config server-only; upload giữ quota reservation kể cả UPLOADING.
- Docker PHP/Nginx lấy public assets từ cùng release build; image chủ động chạy `filament:assets` để không phụ thuộc asset được tạo sẵn trên máy lập trình.

## Ranh giới chưa nghiệm thu

- Browser của phiên không khả dụng (`browsers: []`, `Browser is not available: iab`), nên chưa xác nhận trực quan/keyboard/contrast thực tế ở 360/768/1024/1440px, drag/drop GrapesJS và challenge 2FA trên browser. CSS responsive và HTTP/Livewire test không thay thế browser E2E.
- Chưa kiểm chứng Cloudinary/SMTP production, DNS, HTTPS, VPS. Không deploy production.
- Bộ editor chỉ hỗ trợ section allowlist và các control đang có; chưa phải trình dựng trang tùy ý. Font/spacing/logo bên trong từng section, nâng cấp cấu hình mẫu có migration tương thích và đầy đủ P1 còn cần các lát cắt tiếp theo theo checklist.
- Platform Media hiện là projection read-only scope PLATFORM; editor mẫu có picker ảnh thumbnail PLATFORM/READY/public. Chưa có upload platform mới. Thumbnail tự render từ mẫu vẫn hoạt động mà không cần ảnh upload.
- Notifications/audit/moderation là màn hình nền; chưa hoàn thiện toàn bộ detail/read-state/filter và workflow nghiệp vụ P1. Không có các chức năng Comment/Member/Invitation/Follow/Moderator/daily stats.

## File chính

- Public UI: `resources/views/templates/`, `auth/`, `components/`, `resources/css/app.css`.
- Hai panel: `app/Filament/Pages/Management.php`, `Overview.php`, `app/Filament/Blog/Pages/Studio.php`, các panel provider và `resources/views/filament/`.
- Module Actions/Queries: Templates, Design, Content, Blogs, Media, Platform, Moderation, PublicSite; `DesignDocument` kiểm tra và ánh xạ JSON.
- Tests: `tests/Feature/InterfaceFlowsTest.php` bên cạnh bộ security tests hiện có.

## Bằng chứng kiểm tra phiên này

- `php -d extension=intl vendor/pestphp/pest/bin/pest --colors=never`: 29 tests, 97 assertions đạt (MySQL test database, không phải DB ứng dụng).
- `npm run format:check` (Prettier/Pint), `npm run lint`: đạt. `npm audit` và `composer audit --locked`: không có advisory.
- `npm run build`: đạt; cảnh báo chunk GrapesJS lớn (đã lazy-load) và font fallback tùy chọn. `git diff --check`: đạt, chỉ có cảnh báo chuyển LF/CRLF của README.
- Docker local rebuild application/web và khởi động app/nginx/worker/scheduler thành công; MySQL/Redis khỏe. Sửa lỗi asset khác release giữa Nginx/PHP và thiếu `filament:assets` trong image sạch.
- HTTP 200: `/`, `/templates`, `/login`, `/register`, `/forgot-password`, `/platform-admin/login`; toàn bộ chín CSS/JS Filament được tham chiếu trong trang login trả 200. Đây là smoke test HTTP, không phải nghiệm thu giao diện bằng browser.
- Không chạy migration hoặc DemoSeeder trên DB ứng dụng; không deploy production.
