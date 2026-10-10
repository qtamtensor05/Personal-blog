# Blog Platform — Template, giao diện và cài đặt bằng JSON

Cập nhật ngày 11/10/2026. Bản mới có 29 bảng, 59 khóa ngoại, 12 enum và 6 nhóm. Các yêu cầu đã bỏ ở lượt trước tiếp tục được áp dụng: không bình luận, thành viên, lời mời, follow, Editor/Moderator blog hay thống kê xem bài hằng ngày.

## Những phần vừa gộp

| Phần | Cách lưu mới |
|---|---|
| Template gốc | templates.template_data JSON; một draft_data JSON khi đang sửa |
| Trang mẫu, section, theme defaults/schema, menu mặc định, quy tắc tùy chỉnh | Nằm trong JSON của template; không còn bảng chi tiết template |
| Giao diện của từng blog | Một hàng blog_designs theo blog_id, với published_data/draft_data JSON |
| Menu, thứ tự section, cấu hình layout/theme và binding trang | Trong JSON của blog_designs |
| Nội dung trang tĩnh | Gộp vào pages.content_json, SEO và một draft_data; bỏ page_revisions |
| Platform settings | Một hàng id=1, config_json JSON chứa các nhóm cài đặt |

Đã bỏ thêm section_types, template_versions, template_pages, template_sections, blog_design_revisions, page_revisions, blog_revision_pages, blog_page_sections, navigation_menus và navigation_items. Thêm blog_designs, giảm từ 38 xuống 29 bảng. Mẫu gốc và cấu hình blog vẫn có danh tính khác nhau để nhiều blog cùng dùng một mẫu với nội dung riêng.

## templates — mẫu gốc do Platform Admin quản lý

Các cột name, slug, status, preview_media_id, demo_url, created_by, updated_by, published_by và published_at phục vụ danh sách/tìm kiếm/phân quyền. Cấu trúc thiết kế gộp vào template_data, một JSON envelope gồm:

| Khóa JSON | Mục đích |
|---|---|
| schemaVersion | Phiên bản cấu trúc JSON để validate/migrate |
| project | Nguyên project JSON của GrapesJS, gồm pages/components/styles/assets |
| themeDefaults | Màu, font, kích thước và các giá trị mặc định |
| themeSchema | Schema các trường theme được phép chỉnh |
| sectionRules | Quy tắc khối, trường cho phép sửa, khả năng thêm/ẩn/xóa/sắp xếp |
| pageBindings | Ánh xạ trang của editor sang route và nội dung page/post |
| navigation | Menu mặc định, cây liên kết và thứ tự |

template_data là mẫu đã được công bố, draft_data là tối đa một bản sửa đang chờ. version_number tăng khi publish; không còn bảng lưu lịch sử template. CHECK bảo đảm mỗi template có ít nhất một JSON, mẫu PUBLISHED có template_data và phiên bản public lớn hơn 0. schemaVersion trong JSON và version_number có ý nghĩa khác nhau: cấu trúc tài liệu so với lần công bố mẫu.

Ví dụ cấu trúc envelope để triển khai adapter, không phải toàn bộ project GrapesJS xuất ra từ editor:

```json
{
  "schemaVersion": 1,
  "project": {
    "pages": [
      {
        "id": "home",
        "name": "Trang chủ",
        "frames": [
          {
            "component": {
              "type": "wrapper",
              "components": [
                {
                  "type": "hero",
                  "attributes": {"data-section-key": "home-hero"},
                  "components": "Chào mừng đến blog của tôi"
                },
                {
                  "type": "post-list",
                  "attributes": {"data-section-key": "recent-posts"}
                }
              ]
            }
          }
        ]
      }
    ],
    "styles": [],
    "assets": []
  },
  "themeDefaults": {"primaryColor": "#2563eb", "fontFamily": "Inter"},
  "themeSchema": {
    "type": "object",
    "properties": {"primaryColor": {"type": "string"}, "fontFamily": {"type": "string"}},
    "additionalProperties": false
  },
  "sectionRules": {
    "hero": {"canHide": true, "canReorder": true, "allowedFields": ["title", "description", "mediaId"]},
    "post-list": {"canHide": true, "canReorder": true, "allowedFields": ["limit", "categoryId"]}
  },
  "pageBindings": [{"editorPageId": "home", "route": "/", "kind": "HOME"}],
  "navigation": {
    "header": [{"label": "Trang chủ", "target": {"type": "URL", "url": "/"}}]
  }
}
```

hero/post-list là custom component types phải đăng ký trong GrapesJS và có renderer Blade tương ứng. Registry component chuyển vào mã ứng dụng thay vì bảng section_types. Lưu đầy đủ editor.getProjectData() vào project; không cố thay toàn bộ project bằng vài trường trong ví dụ. Không chạy PHP/Blade/JavaScript tùy ý được nhập từ JSON. Backend chỉ render các type, style và attribute đã cho phép.

## blog_designs — một cấu hình giao diện cho mỗi blog

blog_id vừa là PK vừa là FK tới blogs, nên một blog có tối đa một hàng cấu hình. Hai cặp dữ liệu tương ứng:

| Cột | Mục đích |
|---|---|
| template_id + published_data | Mẫu nguồn và toàn bộ cấu hình đang hiển thị |
| draft_template_id + draft_data | Mẫu nguồn và bản chỉnh sửa/chuyển mẫu đang chờ xuất bản |
| published_at, updated_by | Lần công bố giao diện và người sửa |

Mỗi cặp hoặc cùng NULL hoặc cùng có giá trị, được kiểm tra bằng CHECK. Không có FK vòng active_design_revision_id trên blogs. Không có hàng revision/history giao diện. Việc đổi template trong bản nháp không ghi đè template_id của giao diện đang live.

published_data và draft_data cùng schema: schemaVersion, sourceVersion, project, theme, sectionRules, customizationRules, pageBindings, navigation. JSON lưu snapshot layout và các quy tắc cần render/validate, cùng phần tùy chỉnh của blog. sourceVersion là version_number của mẫu lúc được lấy, không phải FK tới bảng template_versions đã bỏ. Không lấy rules mới nhất của templates để bất ngờ thay đổi hành vi blog đã xuất bản.

Không lưu lại code cho mỗi blog; chỉ lưu dữ liệu cấu hình. Blade component được dùng chung. Khối danh sách bài viết lưu binding và tùy chọn, rồi backend Query Builder truy vấn posts theo blog_id được resolve; không chép toàn bộ nội dung bài vào JSON layout. sourceVersion, schemaVersion, sectionRules và customizationRules là dữ liệu server quản lý; Owner chỉ sửa các trường được phép. Backend không tin bộ rules do client gửi lại để tự cấp thêm quyền tùy chỉnh.

Luồng tạo/sửa/xuất bản:

1. User đã xác minh chọn templates PUBLISHED. Tạo blog DRAFT và blog_designs.draft_data từ mẫu public, gán draft_template_id.
2. Owner sửa JSON nháp qua GrapesJS/section editor theo quy tắc cho phép; validate toàn bộ tài liệu.
3. Preview đọc draft_data chỉ sau kiểm tra quyền Owner hoặc quyền quản trị hỗ trợ.
4. Publish khóa hàng blogs và blog_designs; kiểm tra blog/tài khoản, JSON schema, media, binding, một homepage, route duy nhất, menu không có chu trình và renderer hợp lệ.
5. Cùng transaction chép draft_data sang published_data, draft_template_id sang template_id, cập nhật published_at rồi xóa cặp nháp. Khi cần sửa tiếp, sao chép public sang draft một lần.

Admin cập nhật template gốc không tự sửa published_data của blog. Owner chọn cập nhật mẫu, migrate cấu hình theo schemaVersion/sourceVersion, preview và publish chủ động. version_number chỉ đánh dấu nguồn; bản này không hỗ trợ khôi phục mọi phiên bản template/giao diện cũ. Mẫu bị HIDDEN/RETIRED chặn chọn mới; blog đang dùng snapshot vẫn có thể render, trừ khi chính blog bị khóa. Validate không được phụ thuộc mẫu hiện tại phải PUBLISHED để render blog đã tồn tại.

Tham chiếu page/post/category/media nằm trong JSON không có FK. Service kiểm tra tất cả id hoặc page_key thuộc đúng blog; khi xóa/đổi slug/ẩn tài nguyên phải cập nhật hoặc từ chối nếu layout/menu còn tham chiếu. Tham chiếu menu ưu tiên target PAGE với pageKey hoặc POST với postId rồi server sinh URL. URL bên ngoài phải được kiểm tra protocol. Không chỉ tin blog_id hoặc URL do frontend gửi.

## pages và posts — nội dung độc lập với giao diện

pages giữ title, slug, page_key, status, content_json, SEO, draft_data và lịch đăng trong cùng hàng. posts tương tự với excerpt và cover_media_id. Nội dung Tiptap JSON chứa node ảnh tham chiếu media_id. Backend render và sanitize, không lưu thêm HTML của bài/trang trong bảng. Template JSON giữ layout/binding, không tự trở thành nơi quản lý toàn bộ bài viết.

Giữ pages thành bảng riêng giúp truy vấn URL, trạng thái và SEO trang tĩnh mà không phải tìm trong blob layout. page_key duy nhất theo blog để binding ổn định khi đổi mẫu. Status, slug, blog_id, published_at giữ là cột để lọc/index; không gộp mọi trường vào JSON. Danh mục/tag và bảng nối bài cũng giữ để lọc, tìm kiếm và bảo vệ quan hệ tenant bằng FK ghép.

Với bài/trang DRAFT, ghi nội dung vào cột hiện tại. Với bài/trang đã PUBLISHED, lưu một snapshot đầy đủ của các trường nội dung/SEO được phép vào draft_data; renderer public chỉ đọc cột hiện tại. Publish validate draft_data rồi chép vào cột public và xóa nháp trong transaction. Không có lịch sử nhiều phiên bản.

scheduled_at hẹn giờ bài/trang mới SCHEDULED hoặc bản chỉnh sửa nháp của bài/trang PUBLISHED. Worker khóa hàng, kiểm tra điều kiện rồi publish; sửa nháp sau khi hẹn giờ phải hủy lịch hoặc được người dùng lên lịch lại rõ ràng. Không cho draft_data thay id, blog_id, owner/author hoặc quyền. Thời gian lưu UTC; hiển thị theo blog_settings.timezone.

## platform_settings — một bản ghi JSON

id=1 là PK cố định và có CHECK id=1; không còn key/value/is_public theo từng dòng. config_json là JSON object chứa các nhóm branding, seo, discovery, limits, features, security và schemaVersion. updated_by nullable cho dữ liệu bootstrap, sau đó lưu người cập nhật.

Ví dụ dữ liệu khởi tạo trong seed:

```json
{
  "schemaVersion": 1,
  "branding": {
    "name": "Blog Platform",
    "logoMediaId": null
  },
  "seo": {
    "title": "Blog Platform",
    "description": "Khám phá và tạo blog cá nhân"
  },
  "discovery": {
    "enabled": true
  },
  "limits": {
    "maxBlogsPerUser": 3,
    "defaultStorageQuotaBytes": 104857600,
    "maxImageUploadBytes": 10485760
  },
  "features": {
    "registrationEnabled": true,
    "customDomainsEnabled": false
  },
  "security": {
    "requireAdmin2fa": true,
    "requireVerifiedEmailForBlog": true
  }
}
```

Các giá trị limit trong ví dụ là defaults có thể cấu hình, không phải yêu cầu cứng của dự án. config_json là nguồn cho các mặc định cấp nền tảng; quota riêng từng blog vẫn nằm ở blog_settings. Dịch vụ tạo blog lấy mặc định từ JSON và ghi giá trị được cấp vào blog_settings. Thay đổi mặc định không tự nâng/hạ quota tất cả blog đang có.

Public portal chỉ nhận các khóa được backend cho phép (branding, SEO public, trạng thái đăng ký/khám phá cần thiết), không nhận toàn bộ config_json. Security/quota/thông số nội bộ chỉ có panel và service đọc. Cloudinary/SMTP/API secrets ở env/secret manager, không nằm trong JSON này. Bản này dùng cài đặt nền tảng có hiệu lực khi lưu, không thêm draft/public settings history.

Chỉ Super Admin được sửa cài đặt nhạy cảm, cấu hình bảo mật hoặc giới hạn nền tảng. Validate schema, giới hạn giá trị và allowlist theo permission; chặn ghi đè nguyên JSON bằng payload không kiểm tra. Cập nhật có khóa hàng hoặc cập nhật JSON path ở server để tránh hai Admin ghi đè thay đổi của nhau; ghi admin_audit_logs và invalidation cache sau commit. config_json có CHECK JSON_TYPE='OBJECT', còn validation nội dung là nhiệm vụ service.

## Media Cloudinary

Chỉ media_assets lưu metadata Cloudinary, uploader, scope, blog_id, URL, asset_id/public_id, kích thước, dung lượng và trạng thái. PLATFORM dùng chung được phép; BLOG chỉ trong blog đó; USER cho avatar uploader. Các trường cover/og/logo/favicon/avatar vẫn là FK thông thường; ảnh bên trong JSON dùng media_id và URL hỗ trợ editor.

Backend kiểm tra media_id tồn tại, READY và đúng scope khi lưu mọi JSON. Trước khi xóa ảnh, kiểm tra FK và tất cả JSON hiện có: templates.template_data/draft_data, blog_designs.published_data/draft_data, posts.content_json/draft_data, pages.content_json/draft_data, platform_settings.config_json và các cài đặt có ảnh. Không còn các bảng nối media hay bảng revision cũ cần quét.

Luồng lưu ảnh tham chiếu và luồng xóa cùng khóa hàng media theo thứ tự id. Đánh dấu DELETING để chặn tham chiếu mới; scan thấy đang dùng thì từ chối xóa/khôi phục READY. Chỉ xóa Cloudinary khi không còn dùng, retry/idempotency qua queue vì MySQL và Cloudinary không có transaction chung. Token upload ký ở backend, kiểm tra MIME/dung lượng/quota; không lộ secret.

## Quyền và bảo mật giữ trong thiết kế

| Vai trò | Cách xác định | Phạm vi |
|---|---|---|
| Super Admin | user_platform_roles | Admin, role/permission, bảo mật và toàn nền tảng |
| Platform Admin | user_platform_roles | User thường, template, blog, media nền tảng và báo cáo theo quyền |
| Blog Owner | blogs.owner_user_id | Blog mình sở hữu |
| User | Tài khoản đã đăng ký | Hồ sơ và tạo blog sau xác minh |
| Guest | Chưa đăng nhập | Đọc public |

Owner không có bảng role/member riêng. Query Builder luôn lọc blog_id đã resolve và Policy kiểm tra owner. Platform Admin không tự nâng quyền, không khóa Admin khác hoặc sửa Super Admin. Bảo vệ Super Admin cuối cùng và không tự khóa mình. Các thao tác chuyển Owner, khóa tài khoản/blog, sửa cài đặt ghi audit.

Giữ email verification bằng signed URL, password_reset_tokens với token hash/TTL, database sessions để thu hồi, users.status và account_restrictions, rate limit/Redis/CAPTCHA, 2FA bắt buộc Admin và log bảo mật. Xác minh email không tự mở tài khoản bị hạn chế. Khóa tài khoản cập nhật status + ghi lịch sử + thu hồi session; kiểm tra trạng thái mỗi request. Mở khóa tính lại từ restriction còn hiệu lực và email_verified_at. Mật khẩu hash, secret/recovery codes 2FA mã hóa, secrets không log. Quy trình xóa dùng soft-delete và xử lý blog sở hữu trước purge.

## Hiển thị và các nhóm chức năng

Blog Website đọc blogs PUBLISHED, blog_designs.published_data và posts/pages PUBLISHED. Blog PRIVATE chỉ Owner hoặc Admin có quyền hỗ trợ; UNLISTED bỏ khỏi khám phá; SUSPENDED chặn public. Main Website còn yêu cầu PUBLIC + discoverable=true. Khóa user và khóa blog là hai hành động riêng.

blog_topics phân loại blog trên Main Website; template_categories phân loại mẫu; categories/tags phân loại bài trong blog. Các bảng phân loại và mapping còn giữ cho lọc/search. content_reports nhận BLOG hoặc POST, admin_audit_logs lưu kiểm duyệt, notifications cho thông báo hệ thống, blog_domains cho tên miền tùy chỉnh. Không thêm cộng tác, bình luận, follow hay bảng xem bài.

## Cách dùng và phạm vi kiểm chứng

| File | Công dụng |
|---|---|
| blog_platform_complete.dbml | Mở trong dbdiagram.io để xem đầy đủ cột và khóa |
| blog_platform_mysql.sql | DDL schema mới, MySQL 8.0.16+ để CHECK thực thi |
| seed_roles_permissions.sql | Seed quyền nền tảng và cấu hình mặc định id=1; không tạo Admin/password |
| erd_overview.svg / erd_overview.png | Sơ đồ nhóm tổng quan |
| erd_explorer.html | Tra cứu schema offline |
| schema_manifest.json | Mô tả schema cho công cụ |
| validation_report.txt | Các kiểm tra thực tế và giới hạn |

DDL/seed dành cho database mới, không phải migration xóa/gộp dữ liệu đang có. Nếu đã dùng bản 38 bảng, cần chuyển project/theme/rules từ version template được sử dụng vào JSON, gom menu/section vào blog_designs, chép revision trang public/nháp vào pages và gom các settings theo key; kiểm tra trước khi bỏ bảng. Các file này không tự chạy thao tác xóa dữ liệu.

Chọn database rỗng, chạy DDL rồi seed. Không chồng lên migrations Laravel mặc định đã tạo users/sessions/password_reset_tokens. DDL tạo bảng trước và thêm FK sau, không có DROP và không tắt FK. updated_at do ứng dụng cập nhật. Kiểm tra FK/CHECK không thay cho JSON schema, quyền, tenant và quy tắc publish. SQL chưa được chạy trên MySQL thực tế trong môi trường tạo file.

Stack giữ PHP/Laravel, Filament, Livewire, Alpine.js, Blade, Tailwind, Vite, Query Builder, Eloquent tối thiểu cho auth/Filament, MySQL, Cloudinary, GrapesJS, Tiptap và Redis. Không đổi lựa chọn công nghệ; bản này chỉ đơn giản hóa cách lưu dữ liệu.

## Nguồn kỹ thuật

- Laravel email verification: https://laravel.com/docs/13.x/verification
- GrapesJS project JSON: https://grapesjs.com/docs/modules/Storage.html
- Tiptap JSON: https://tiptap.dev/docs/guides/output-json-html
- Cloudinary identifiers: https://cloudinary.com/documentation/ts_asset_id_vs_public_id
- MySQL FK: https://dev.mysql.com/doc/refman/8.4/en/create-table-foreign-keys.html

## Từ điển bảng

### 01_AUTH

| Bảng | Mục đích |
|---|---|
| `users` | Tài khoản chung. User là mặc định; quyền quản trị cấp qua user_platform_roles; quyền Owner suy ra từ blogs.owner_user_id. |
| `platform_roles` | SUPER_ADMIN và PLATFORM_ADMIN. Chủ blog xác định bằng blogs.owner_user_id. |
| `permissions` | Quyền platform.* cho quản trị nền tảng. Owner dùng Policy kiểm tra blogs.owner_user_id; không có RBAC thành viên blog. |
| `platform_role_permissions` | Quyền của vai trò nền tảng. |
| `user_platform_roles` | Gán quyền quản trị nền tảng. User thường không cần bản ghi tại đây. |
| `sessions` | Database session driver Laravel. Thu hồi bằng xóa session; last_activity là Unix timestamp. |
| `password_reset_tokens` | Theo cấu trúc password broker Laravel. Token được hash và có TTL ở cấu hình. |
| `account_restrictions` | Lịch sử khóa/đình chỉ/vô hiệu hóa; đồng bộ users.status trong transaction. Mở khóa không mặc định ACTIVE nếu email chưa xác thực. |
| `security_audit_logs` | Append-only. Không lưu password, OTP, token, recovery code. actor/subject nullable cho thất bại đăng nhập. |

### 02_BLOG

| Bảng | Mục đích |
|---|---|
| `blogs` | Tenant gốc, một chủ sở hữu qua owner_user_id. Không có thành viên, lời mời hoặc vai trò Editor. Trạng thái và visibility độc lập. |
| `blog_settings` | 1:1 cài đặt riêng mỗi blog; JSON cho liên kết xã hội và giới hạn do nền tảng cấp. |
| `blog_topics` | Chủ đề blog trên Main Website; khác danh mục bài viết trong từng blog. |
| `blog_topic_assignments` | Phân loại blog trong thư viện khám phá. |
| `blog_domains` | Mở rộng: tên miền tùy chỉnh; chỉ phục vụ khi xác minh chủ sở hữu DNS và HTTPS thành công. |

### 03_TEMPLATE_DESIGN

| Bảng | Mục đích |
|---|---|
| `templates` | Một hàng cho mỗi mẫu. Cấu trúc trang/section/theme/menu/rules gộp vào template_data JSON; draft_data giữ một bản nháp. Không có các bảng chi tiết hoặc lịch sử template. |
| `blog_designs` | 1:1 với blog. published_data/draft_data lưu toàn bộ layout, theme, sections, menu và snapshot template; publish đổi JSON trong một transaction, không tạo hàng revision. |
| `template_categories` | Loại mẫu/ngành/chủ đề trong thư viện template như ảnh tham khảo. |
| `template_category_assignments` | N:M phân loại template. |

### 04_CONTENT

| Bảng | Mục đích |
|---|---|
| `posts` | Một hàng cho mỗi bài: nội dung Tiptap JSON, ảnh cover và SEO. draft_data giữ tối đa một bản chỉnh sửa chờ đăng; không có lịch sử phiên bản bài viết. |
| `pages` | Nội dung và SEO trang tĩnh cùng một hàng; content_json là Tiptap JSON. Layout và menu nằm trong blog_designs. page_key ổn định để binding. |
| `categories` | Danh mục bài viết trong từng blog; không dùng lại blog_topics. |
| `tags` | Tag riêng trong mỗi blog. |
| `post_categories` | N:M với composite FK bảo vệ phạm vi blog. |
| `post_tags` | N:M với composite FK bảo vệ phạm vi blog. |

### 05_MEDIA

| Bảng | Mục đích |
|---|---|
| `media_assets` | Một thư viện Cloudinary dùng chung. JSON nội dung lưu media_id và URL; không có bảng liên kết media riêng. Backend kiểm tra quyền và tất cả tham chiếu JSON trước khi xóa ảnh. |

### 06_OPERATIONS

| Bảng | Mục đích |
|---|---|
| `content_reports` | Báo cáo blog hoặc bài viết. Không có báo cáo bình luận. Platform Admin xử lý theo quyền; thay đổi ghi vào admin_audit_logs. |
| `admin_audit_logs` | Thao tác nền tảng và blog. resource_type/resource_id là snapshot log, không phải quan hệ đa hình bảo đảm FK. |
| `platform_settings` | Một bản ghi id=1. config_json chứa branding/SEO/discovery/limits/features/security; backend chỉ trả các khóa public được cho phép. Secrets ở env. |
| `notifications` | Thông báo cho user; delivery email do queue thực hiện. recipient_user_id là FK trực tiếp nên cần custom notification channel/mapping nếu dùng Laravel DatabaseNotification mặc định. |

