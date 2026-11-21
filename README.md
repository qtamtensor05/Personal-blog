# Personal Blog

Nền tảng blog cá nhân viết bằng Laravel 13, theo Clean Architecture dạng modular monolith. Admin tạo và xuất bản template dùng chung; mỗi người dùng tạo blog, áp template, tùy chỉnh thiết kế riêng và viết bài bằng Tiptap.

## Tính năng

- **Tài khoản:** đăng ký, xác minh email, đăng nhập bằng session, quên và đặt lại mật khẩu qua SMTP.
- **Phân quyền nền tảng:** role và permission (`platform_roles`, `permissions`), Admin Console được bảo vệ bằng middleware `permission:<code>`.
- **Template:** Admin tạo bản nháp, cấu hình section và design token, xuất bản (tăng `version_number`), ẩn hoặc ngừng dùng.
- **Blog:** tạo blog, cài đặt (ngôn ngữ, múi giờ, SEO, mạng xã hội), áp template và tùy chỉnh màu/font. Thiết kế của blog là bản chụp của template, nên Admin sửa template không ghi đè thiết kế riêng.
- **Nội dung:** bài viết và trang tĩnh soạn bằng Tiptap (tự lưu nháp, xuất bản), category và tag.
- **Media:** upload ảnh lên Cloudinary bằng chữ ký tạo ở backend, metadata lưu trong `media_assets`, có giới hạn dung lượng theo blog.
- **Trang public:** `/b/{blog}`, `/b/{blog}/{post}`, `/b/{blog}/p/{page}`. Chỉ hiển thị nội dung đã xuất bản.
- **Domain riêng:** xác minh bằng bản ghi DNS TXT, sau đó blog được phục vụ trên domain của người dùng.
- **Kiểm duyệt:** người đọc báo cáo blog, bài viết hoặc bình luận; admin nhận thông báo và xử lý. Báo cáo và công việc gửi thông báo được lưu cùng transaction trong `report_notification_outbox`; gửi lỗi sẽ được thử lại, không yêu cầu người dùng gửi lại báo cáo. Thao tác của Admin được ghi vào `admin_audit_logs`.

### Thử lại thông báo báo cáo

Sau khi cập nhật, chạy `php artisan migrate` trong `website` để tạo bảng outbox.
Laravel scheduler cần chạy (`php artisan schedule:work` khi phát triển, hoặc cron gọi
`php artisan schedule:run` mỗi phút trên server). Tác vụ `reports:deliver-notifications`
thử lại các thông báo chưa gửi mỗi phút; cũng có thể chạy thủ công bằng
`php artisan reports:deliver-notifications`. Các thông báo và dấu đã gửi được commit
cùng nhau, nên thử lại không tạo thông báo trùng. Lỗi gửi được ghi vào log Laravel.

## Yêu cầu

- PHP 8.3 trở lên và Composer
- Node.js 22.12+ (hoặc 20.19+) và npm; Vite 8 không chạy với Node cũ hơn
- Docker và Docker Compose (MySQL 8.4, Redis)
- Tài khoản Cloudinary và một SMTP server (tùy chọn khi phát triển)

Migration chỉ chạy trên MySQL 8.0.16 trở lên vì schema dùng CHECK constraint. SQLite và MariaDB không được hỗ trợ.

PHP cần extension `pdo_mysql` và `redis` (ngoài các extension Composer kiểm tra). Kiểm tra bằng `php -m` và `composer check-platform-reqs` trong `website`.

## Cài đặt lần đầu

```bash
cd website
composer install
php -r "file_exists('.env') || copy('.env.example', '.env');"
php artisan key:generate
```

`.env.example` đã có sẵn giá trị khớp với `docker-compose.yml` (MySQL database `blog`, Redis cho queue và cache, session lưu trong database). Thường chỉ cần điền SMTP và Cloudinary trong `website/.env`.

### Biến môi trường

| Biến                              | Giá trị                                            | Ghi chú                                                                                 |
| --------------------------------- | -------------------------------------------------- | --------------------------------------------------------------------------------------- |
| `APP_URL`                         | `http://localhost:8000`                            | Host của URL này là host của nền tảng. Mọi host khác được coi là domain riêng của blog. |
| `DB_*`                            | `127.0.0.1`, `3306`, `blog`, `root`                | Chỉ MySQL 8.0.16 trở lên.                                                               |
| `SESSION_DRIVER`                  | `database`                                         | Không đổi: khóa tài khoản và đặt lại mật khẩu xóa session trong bảng `sessions`.        |
| `QUEUE_CONNECTION`, `CACHE_STORE` | `redis`                                            | Cần container `redis` đang chạy.                                                        |
| `MAIL_*`                          | xem bên dưới                                       | Dùng `MAIL_MAILER=log` khi phát triển để ghi email vào `storage/logs` thay vì gửi.      |
| `CLOUDINARY_URL`                  | `cloudinary://<api_key>:<api_secret>@<cloud_name>` | Chỉ dùng ở server, không đưa xuống frontend.                                            |
| `CLOUDINARY_FOLDER_PREFIX`        | `personal-blog`                                    | Thư mục gốc trên Cloudinary, giúp nhiều môi trường dùng chung một tài khoản.            |
| `MEDIA_MAX_UPLOAD_KB`             | `5120`                                             | Kích thước ảnh tối đa (KB). Định dạng cho phép: jpg, png, webp, gif.                    |

### Gửi email qua Gmail

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=you@gmail.com
MAIL_PASSWORD=<app password 16 ký tự>
MAIL_FROM_ADDRESS="you@gmail.com"
```

- `MAIL_HOST` là tên server SMTP, không phải địa chỉ email. Địa chỉ Gmail đặt ở `MAIL_USERNAME`.
- `MAIL_PASSWORD` là App Password tạo ở Google Account > Security > App passwords (cần bật xác minh 2 bước), không phải mật khẩu Gmail.
- Nếu `MAIL_FROM_ADDRESS` khác tài khoản đăng nhập, Gmail sẽ đổi người gửi về tài khoản đó, trừ khi địa chỉ này đã được thêm vào "Send mail as".

### Cloudinary

Lấy `CLOUDINARY_URL` ở Cloudinary Console > Settings > API Keys (dòng "API environment variable"). Chỉ dán phần giá trị:

```env
CLOUDINARY_URL=cloudinary://123456789012345:abcdefGHIJ@my-cloud
```

Không dán cả dòng `CLOUDINARY_URL=...` vào sau dấu `=`. Nếu giá trị sai, việc upload sẽ báo lỗi `CLOUDINARY_URL must look like cloudinary://...`.

Các lệnh sau chạy trong thư mục `website` (nơi có file `artisan`, `composer.json` và `package.json`). Điền `BOOTSTRAP_ADMIN_EMAIL` và `BOOTSTRAP_ADMIN_PASSWORD` trong `.env` trước khi seed; mật khẩu cần ít nhất 12 ký tự.

Khởi động database, chờ MySQL healthy, chạy migration và seed dữ liệu ban đầu:

```bash
docker compose up -d mysql redis
docker compose ps
php artisan config:clear
php artisan migrate --seed
npm install
```

Mở Docker Desktop và chờ engine sẵn sàng trước khi chạy. `--wait` chờ cả MySQL và Redis healthy; nếu lệnh thất bại, xử lý lỗi trước khi migrate. Nếu `storage:link` báo liên kết đã tồn tại thì có thể tiếp tục. Docker Compose ở môi trường local cho phép mật khẩu root MySQL rỗng. Không dùng cấu hình này cho production.

## Tạo Admin đầu tiên

`php artisan migrate --seed` chạy `DatabaseSeeder`: tạo role/permission, tạo tài khoản Owner từ `BOOTSTRAP_ADMIN_EMAIL` và `BOOTSTRAP_ADMIN_PASSWORD`, cấp `SUPER_ADMIN`, rồi seed template ban đầu. Tài khoản mới có trạng thái ACTIVE và email đã xác minh; đăng nhập bằng thông tin đã cấu hình trong `.env`.

Nếu database đã migrate, chỉ cần chạy:

```bash
php artisan config:clear
php artisan db:seed
```

Seed lại không ghi đè mật khẩu hoặc trạng thái của tài khoản đã tồn tại. Thay mật khẩu trong `.env` không đổi mật khẩu tài khoản cũ; dùng chức năng đặt lại mật khẩu. `php artisan migrate` riêng lẻ chỉ cập nhật schema; `php artisan db:seed --class=AccessControlSeeder` chỉ tạo role/permission, không tạo tài khoản.

Để cấp quyền cho một tài khoản khác đã đăng ký và xác minh email:

```bash
php artisan admin:grant you@example.com
```

Lệnh chỉ cấp role cho tài khoản đang ACTIVE và ghi lại vào `security_audit_logs`. Từ đó, Admin cấp role cho người khác tại `/admin/users`.

## Chạy ứng dụng

Các lệnh dưới đây đều chạy trong `website`. Mỗi lần chạy lại dự án, mở Docker Desktop rồi chạy:

```bash
docker compose up -d --wait mysql redis
php artisan config:clear
composer run dev
```

Lệnh này chạy Laravel server, queue worker và Vite dev server. Giữ terminal này mở; Docker Compose chỉ chạy database/cache, không chạy web. Mở địa chỉ trong `APP_URL` của `.env` (mặc định http://localhost:8000).

Nếu đang ở trong `website` thì không chạy `cd website` lần nữa. Trên PowerShell, có thể chuyển thẳng bằng `cd C:\Users\quangTam\Documents\Personal-blog\website`. Lỗi `Could not open input file: artisan` hoặc npm không tìm thấy `package.json` thường do chạy sai thư mục.

Bật thêm phpMyAdmin tại http://localhost:8080:

```bash
docker compose --profile dev up -d
```

## Các màn hình chính

| Khu vực                    | URL                                                                       | Ai được vào                       |
| -------------------------- | ------------------------------------------------------------------------- | --------------------------------- |
| Blog của tôi               | `/my-blogs`, `/blogs/{id}`                                                | Người đã đăng nhập (chỉ chủ blog) |
| Thiết kế, cài đặt          | `/blogs/{id}/design`, `/blogs/{id}/settings`                              | Chủ blog                          |
| Bài viết, trang, phân loại | `/blogs/{id}/posts`, `/blogs/{id}/pages`, `/blogs/{id}/taxonomy`          | Chủ blog                          |
| Domain riêng               | `/blogs/{id}/domains`                                                     | Chủ blog                          |
| Thư viện template          | `/templates`                                                              | Người đã đăng nhập                |
| Admin Console              | `/admin/templates`, `/admin/users`, `/admin/reports`, `/admin/audit-logs` | Có permission tương ứng           |
| Trang public               | `/b/{blog}`, `/b/{blog}/{post}`, `/b/{blog}/p/{page}`                     | Mọi người                         |

## Kiến trúc

```
website/app/
├── Domain/          Entity, value object, enum. Không phụ thuộc framework.
├── Application/     Use case, contract (interface), DTO.
├── Infrastructure/  Repository Query Builder, Cloudinary, mail, DNS.
├── Presentation/    Controller, request, middleware, Blade view, CSS, JS.
└── Providers/       Một service provider cho mỗi module, bind contract với implementation.
```

Module: Identity, AccessControl, Administration, Blogs, BlogDesign, Templates, Content, Media, Moderation, Domains.

Quy ước:

- **Persistence:** toàn bộ truy cập dữ liệu dùng Query Builder (`DB::table`), không dùng Eloquent. Session auth dùng provider `database`.
- **Phạm vi blog:** mọi truy vấn nội dung đều lọc theo `blog_id`, khớp với các composite foreign key trong schema.
- **Nội dung Tiptap:** được lọc phía server theo danh sách node, mark và thuộc tính cho phép trước khi lưu. Ảnh tham chiếu `media_assets` qua `mediaId`, URL được lấy ở server khi render.
- **Bản nháp và bản xuất bản:** template dùng `draft_data` / `template_data`; thiết kế blog dùng `draft_data` / `published_data`; bài viết và trang dùng `draft_data` / `content_json`. Trang public chỉ đọc phần đã xuất bản.

## Domain riêng

1. Chủ blog thêm hostname tại `/blogs/{id}/domains`.
2. Tạo bản ghi TXT `_personal-blog-verify.<hostname>` với giá trị hiển thị trên trang, rồi bấm Verify.
3. Trỏ CNAME hoặc A record của domain về server, rồi Activate.

Server cần chứng chỉ TLS cho domain đó; phần này cấu hình ở reverse proxy, không nằm trong ứng dụng. Trên domain riêng chỉ có trang chủ, bài viết và trang tĩnh. Đăng nhập, workspace và Admin chỉ có ở host trong `APP_URL`.

## Kiểm tra code

Chạy trong `website`:

```bash
./vendor/bin/pint            # format PHP
./vendor/bin/pint --test     # chỉ kiểm tra
php artisan test             # Pest
npm run lint                 # ESLint
npm run build                # build asset bằng Vite
```

## Bảo mật

- Không commit `.env`. `CLOUDINARY_URL`, thông tin SMTP và `APP_KEY` chỉ nằm ở môi trường.
- Cloudinary API secret không bao giờ được gửi xuống frontend.
- Token đặt lại mật khẩu và token xác minh domain chỉ lưu dạng hash.
- Tài khoản bị khóa, đình chỉ hoặc vô hiệu hóa bị đăng xuất khỏi mọi session ngay lập tức.

## Trợ lý viết nội dung từ ảnh

Hướng dẫn tính năng mới: [Trợ lý viết nội dung từ ảnh bằng Gemini](website/docs/IMAGE_WRITING.md) — cấu hình khóa API, cách dùng trong trình soạn thảo và giới hạn hỗ trợ.

## Giới hạn hiện tại

- Chưa có Media Library: ảnh bìa, logo và ảnh OG nhập bằng media ID.
- Trạng thái `SCHEDULED` của bài viết có trong schema nhưng chưa dùng (cần scheduler).
- Chưa có notification và platform settings.
- Các cột 2FA trong bảng `users` chưa được sử dụng.
