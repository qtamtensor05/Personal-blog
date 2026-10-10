# Trạng thái hiện tại

Ngày cập nhật: 2026-10-11.

Đã hoàn thành nền tảng chạy được gồm schema chuẩn 29 bảng/59 khóa ngoại, platform seed, demo seed tùy chọn, bootstrap admin, xác thực session + email verification/status gate, luồng blog theo chủ sở hữu, truy vấn public blog/bài viết/trang, publish action có transaction/audit/fingerprint, bộ kiểm tra component an toàn, module Tiptap/GrapesJS, giao diện tiếng Việt responsive và Docker topology đầy đủ.

## Bằng chứng kiểm tra hiện tại

- MySQL 8.4 migrate fresh + seed thành công; xác nhận 29 bảng nghiệp vụ, 59 khóa ngoại và 13 CHECK constraint.
- PHPUnit/Pest: 8 test, 11 assertion đều đạt trên MySQL.
- Vite production build, ESLint, Prettier/Pint, Composer validate/audit và npm audit đều đạt; không có advisory đã biết.
- Docker production image build thành công với `--no-dev`.
- Stack `app`, `nginx`, `worker`, `scheduler`, `mysql`, `redis`, `mailpit` khởi động thành công; `GET /` và `GET /up` trả HTTP 200.
- Scheduler đã thực thi `publish-scheduled-content` thành công trong container.

## Ranh giới chưa nghiệm thu production

Chưa xác minh dịch vụ thật vì chưa được cấp Cloudinary URL, SMTP production, domain hay VPS. Mailpit và adapter/test local là ranh giới kiểm thử. Custom domain vẫn tắt. Các màn hình CRUD quản trị sâu của Filament, toàn bộ luồng challenge 2FA, media lifecycle hoàn chỉnh và browser E2E toàn hành trình cần được bổ sung trước khi coi hệ thống là production-ready theo toàn bộ prompt.

## Gia cố bảo mật 2026-10-11

- Production Compose không còn publish cổng MySQL/Redis; Nginx chỉ bind `127.0.0.1`.
- Production ép `APP_ENV=production`, `APP_DEBUG=false`, log warning, mã hóa session và cookie Secure.
- Media trong Tiptap và khi publish bài viết được xác thực theo đúng `blog_id`, trạng thái READY và scope BLOG.
- Upload ảnh giới hạn định dạng, dung lượng và kích thước pixel.
- Audit log dùng đúng cột canonical; scheduled publish ghi actor là chủ blog.
- Tài khoản có `locked_until` trong tương lai bị chặn ở login, middleware và Filament panel.
- Nginx thêm CSP, chống MIME sniffing/clickjacking, Referrer Policy và Permissions Policy.
- Pest sau gia cố: 13 test, 24 assertion đều đạt trên MySQL.
