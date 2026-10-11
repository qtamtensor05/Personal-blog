# Bút Mây — Multi-Tenant Blog Platform

Laravel 13, Filament 5, Livewire 4, Blade, GrapesJS, Tiptap, MySQL 8.4, Redis và Cloudinary SDK. Ứng dụng nằm trong `website/`; ERD chuẩn và tài liệu triển khai nằm trong `website/docs/`.

## Chạy bằng Docker trên Windows PowerShell

```powershell
Set-Location .\website
Copy-Item .env.example .env
docker compose --profile local up -d --build
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
docker compose exec app php artisan db:seed --class=DemoSeeder
```

Mở http://localhost:8000. Mailpit: http://localhost:8025. phpMyAdmin: http://localhost:8080.

Tạo Super Admin đầu tiên (không có mật khẩu mặc định trong source):

```powershell
docker compose exec app php artisan platform:bootstrap-admin
```

## Chạy trực tiếp

PHP CLI phải bật `intl`, `pdo_mysql`, `mbstring`, `zip` và `redis`.

```powershell
Set-Location .\website
composer install
npm ci
Copy-Item .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run build
php artisan filament:assets
composer run dev
```

Nếu chạy app trực tiếp thay vì trong Compose, đổi `DB_HOST=127.0.0.1`, `REDIS_HOST=127.0.0.1` và mở port dịch vụ local tương ứng.

## Kiểm tra

```powershell
php artisan test
vendor\bin\pint --test
npm run lint
npm run format:check
npm audit
npm run build
docker compose config
```

Migration nghiệp vụ cố ý chỉ chạy trên MySQL 8.0.16+; SQLite không đại diện được CHECK/JSON/composite FK của ERD. Cloudinary, SMTP production, DNS/HTTPS custom domain và VPS cần credentials/hạ tầng thật trước khi kiểm chứng live.

Xem [trạng thái hiện tại](website/docs/current-state.md), [kiến trúc](website/docs/architecture/README.md), [đối chiếu database](website/docs/database/migration-comparison.md) và [runbook VPS](website/docs/deployment/VPS.md).

## Giao diện và luồng sử dụng

- `/` hoặc `/templates`: gallery, tìm/lọc mẫu, preview và đăng nhập. Chọn **Dùng mẫu** sẽ giữ lựa chọn qua đăng nhập/đăng ký và xác minh email.
- `/workspace`: tạo blog từ mẫu đã xuất bản; blog mới có giao diện nháp riêng.
- `/platform-admin/management?section=templates`: quản lý mẫu bằng dữ liệu thật, lưu nháp, editor section, xuất bản/ngừng cho chọn. Admin đăng nhập tại `/platform-admin/login` và hoàn tất 2FA.
- `/blog-admin/studio?blog=<id>`: quản lý blog của Owner. Preview nháp yêu cầu đăng nhập đúng Owner; public chỉ đọc snapshot đã xuất bản.

Demo là tùy chọn, chỉ chạy khi `APP_ENV=local` hoặc `testing`; gồm ba mẫu Nhật ký, Hành trình, Tạp chí và hai blog minh họa. **DemoSeeder có thể ghi đè các bản ghi demo cùng slug/email; không chạy trên dữ liệu cần giữ.** Không chạy `migrate:fresh` để cập nhật giao diện.

Khi chạy Docker, source PHP được đóng trong image, vì vậy sau khi sửa code cần:

```powershell
docker compose up -d --build app worker scheduler nginx
docker compose restart nginx
docker compose exec app php artisan optimize:clear
```

Nếu chạy trực tiếp, ngoài `composer run dev`, mở terminal khác chạy scheduler:

```powershell
php artisan schedule:work
```

Worker dùng `php artisan queue:work redis --tries=3 --timeout=90`; lịch bài/trang được xử lý bởi scheduler. Thời gian nhập lịch trong UI hiện là UTC. Upload ảnh cần `CLOUDINARY_URL` (server-only); đặt `CLOUDINARY_CLOUD_NAME` nếu cần. SMTP local trong Docker dùng `MAIL_HOST=mailpit`, `MAIL_PORT=1025` với profile `local`; không dùng `127.0.0.1` để gọi Mailpit từ container app.

Chi tiết phạm vi, giả định và kiểm chứng: [bàn giao giao diện](website/docs/ui-implementation-handoff.md).
