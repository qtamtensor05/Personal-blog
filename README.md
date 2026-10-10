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
