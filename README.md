# Bút Mây — Blog Platform

Laravel / Filament / Livewire / MySQL / Redis. Source nằm trong website/. Khuyên dùng Docker.

## 1. Cài mới bằng Docker (PowerShell)

Cần Docker Desktop đang chạy, Linux containers và Compose v2. Mở terminal tại thư mục repository.

```powershell
Set-Location .\website
if (!(Test-Path .env)) { Copy-Item .env.example .env }
docker compose build
docker compose run --rm --no-deps app php artisan key:generate --show
```

Chép chuỗi base64:... vừa tạo vào APP_KEY trong **website/.env trên máy**, đặt MAIL_HOST=mailpit, rồi lưu file. Nếu đã có key, giữ nguyên và bỏ qua bước tạo key. Không chia sẻ .env; không tạo key chỉ trong container vì sẽ mất khi recreate.

```powershell
docker compose --profile local up -d
docker compose exec app php artisan migrate --seed
docker compose exec app php artisan platform:bootstrap-admin
```

Lệnh admin hỏi email, tên, mật khẩu (ít nhất 12 ký tự); chỉ chạy một lần cho mỗi email. Không có mật khẩu admin mặc định.

| Mục                                          | Địa chỉ                              |
| -------------------------------------------- | ------------------------------------ |
| Website / chọn mẫu                           | http://localhost:8000                |
| Platform Admin (thiết lập 2FA khi đăng nhập) | http://localhost:8000/platform-admin |
| Blog của tôi (cần xác minh email)            | http://localhost:8000/workspace      |
| Email thử nghiệm Mailpit                     | http://localhost:8025                |
| Quản lý DB phpMyAdmin                        | http://localhost:8080                |

**Demo tùy chọn:** gallery có thể trống khi chưa tạo/xuất bản mẫu. Muốn có ba mẫu và hai blog minh họa:

```powershell
docker compose exec app php artisan db:seed --class=DemoSeeder
```

Demo chỉ chạy ở local/testing, có thể ghi đè bản ghi demo cùng slug/email. Không chạy trên dữ liệu cần giữ; không dùng migrate:fresh để cập nhật UI.

## 2. Chạy lại, cập nhật và dừng

Các lệnh sau chạy trong website/:

```powershell
# Chạy lại, giữ dữ liệu
docker compose --profile local up -d

# Sau khi sửa source hoặc .env
docker compose up -d --build --force-recreate app nginx worker scheduler
docker compose exec app php artisan optimize:clear

# Kiểm tra trạng thái / log
docker compose ps
docker compose logs --tail=80 app worker scheduler

# Dừng, giữ volume database
docker compose --profile local down
```

Không dùng down -v nếu cần giữ dữ liệu. Khi có migration mới, backup DB rồi chạy migrate --seed. Docker đã chạy worker và scheduler.

## 3. Chạy PHP trực tiếp (tùy chọn)

Cần PHP 8.4+, Composer 2, Node.js 24 và Docker Desktop. PHP CLI bật intl, pdo_mysql, mbstring, zip, redis (kiểm tra bằng php -m).

Tại website/, tạo .env nếu chưa có. Đặt DB_HOST=127.0.0.1, REDIS_HOST=127.0.0.1, MAIL_HOST=127.0.0.1; giữ port/mật khẩu khớp Compose. Không chạy đồng thời app Docker trên port 8000.

```powershell
docker compose --profile local up -d mysql redis mailpit
composer install
npm ci
# Chỉ cài mới, khi APP_KEY còn trống:
php artisan key:generate
php artisan migrate --seed
php artisan filament:assets
npm run build
composer run dev
```

composer run dev chạy web, queue và Vite. Terminal thứ hai (cũng trong website/) chạy:

```powershell
php artisan schedule:work
```

Tạo admin/demo bằng cùng lệnh Artisan ở mục 1, bỏ tiền tố docker compose exec app.

## 4. Kiểm tra dành cho lập trình viên

Chạy trên máy trong website/ sau composer install và npm ci; image Docker ứng dụng không chứa test/dev dependencies.

Pest dùng MySQL **blog_test** theo phpunit.xml; tạo DB này riêng trước khi chạy (ví dụ qua phpMyAdmin: CREATE DATABASE blog_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;). Bộ test reset schema test; tuyệt đối không trỏ test sang DB ứng dụng. Nếu đổi port/mật khẩu local, cập nhật cấu hình test tương ứng.

```powershell
php artisan config:clear
php artisan test
npm run format:check
npm run lint
npm run build
npm audit
composer audit --locked
docker compose config --quiet
```

## Lưu ý và tài liệu

Test cạnh tranh mục 1: php artisan test --testsuite=Concurrency (đã có trong full suite). Cần DB riêng có hậu tố _test, quyền đọc information_schema.innodb_trx; suite reset schema test, không chạy song song với suite khác trên cùng DB. Xem [kịch bản QA tài khoản/2FA](website/docs/setup/identity-browser-qa.md).

- Upload ảnh cần CLOUDINARY_URL server-only; không cần Cloudinary để xem gallery. SMTP local dùng Mailpit, không gửi email thật.
- Tài khoản: /account sửa tên, đổi email/mật khẩu (cần mật khẩu hiện tại). Đổi mật khẩu đăng xuất các phiên; đổi email phải xác minh lại qua Mailpit. Super Admin cuối cùng cần có Super Admin thứ hai trước khi đổi email.
- Vai trò/quản trị viên: /platform-admin/access-management, chỉ người có quyền quản lý tương ứng. Gán cho tài khoản ACTIVE đã xác minh; thay đổi quyền thu hồi phiên. Role hệ thống chỉ đọc; không tự thay đổi grant của chính mình.
- Thời gian nhập lịch trong UI hiện là UTC. Chỉ giao diện đã xuất bản mới xuất hiện public; sửa mẫu không tự đổi blog live.
- Lỗi thiếu key: kiểm tra APP_KEY trong .env trên máy rồi recreate app/worker/scheduler. Lỗi email Docker: kiểm tra MAIL_HOST=mailpit và profile local.
- Lỗi port bị chiếm: dừng dịch vụ trùng hoặc đổi port forwarding trong Compose/.env; đồng bộ APP_URL khi đổi cổng web.
- Không deploy production theo các bước local. Xem [runbook VPS](website/docs/deployment/VPS.md).
- [Trạng thái](website/docs/current-state.md) · [Kiến trúc](website/docs/architecture/README.md) · [Bàn giao UI và giới hạn kiểm chứng](website/docs/ui-implementation-handoff.md).
- Tài liệu ERD trong website/docs/database/ được quản lý trong Git. Migration/seed sử dụng hai file SQL bắt buộc trong website/database/sql/; không bỏ các file runtime SQL này khi commit. Không commit .env hoặc bản dump chứa dữ liệu thật.
