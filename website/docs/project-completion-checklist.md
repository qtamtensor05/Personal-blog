# Review và checklist hoàn thành

Ngày: 2026-10-11. Đối chiếu `website/codex_blog_platform/PROMPT_CODEX_BLOG_PLATFORM.md`, ERD trong `docs/database`, source và regression tests. Prompt tại root không còn tồn tại; dùng bản trong website.

## Tình trạng

Nền tảng/MVP chạy được; chưa đủ nghiệm thu production. Panel load được không chứng minh CRUD hoàn chỉnh. Các module JavaScript editor chưa được mount thành luồng giao diện end-to-end. Thiếu credentials chỉ chặn kiểm chứng provider thật, không giải thích các module nội bộ còn thiếu.

## Checklist theo giai đoạn

### 1. Nền tảng, identity/access

- [x] Laravel 13/PHP 8.4, Filament 5/Livewire 4, lockfiles.
- [x] Migration/seed chuẩn 29 bảng nghiệp vụ/59 FK; tài liệu đối chiếu ERD.
- [x] Session login/logout/register, signed email verification, CSRF, throttle cơ bản.
- [x] Platform permission expiry, bootstrap Super Admin, cấu hình required 2FA.
- [x] Account/email/locked_until guard tại protected routes và Actions hiện có.
- [ ] Password reset/change, profile thường, đổi email xác minh lại, session/remember-token revoke.
- [ ] Chặn deleted_at tại auth provider; restriction lifecycle + session revoke + security audit.
- [ ] Browser tests enroll/challenge/recovery 2FA, đặc biệt login chung rồi vào platform.
- [ ] CRUD quyền/admin; policy chống nâng quyền, khóa Admin khác và mất Super Admin cuối cùng.

### 2. Blog và tenant

- [x] Create/list/detail owner-only, quota cơ bản; public path resolver lọc status/visibility/deletion.
- [ ] Filament tenant selector, onboarding/chọn template, CRUD blog/settings/topics.
- [ ] Private reader được authorize và support permission rõ ràng.
- [ ] Quota/slug race tests nhiều connection; xử lý unique collision thành validation error.

### 3. Content/media

- [x] Cloudinary adapter, MIME/size/pixel validation, metadata UPLOADING/READY/FAILED.
- [x] Tenant-scoped media rendering và kiểm tra cover/OG/content khi publish post.
- [x] Publish post transaction/audit/JSON draft; scheduler nội dung SCHEDULED mới.
- [ ] Filament post/page/category/tag CRUD; draft save/autosave/fingerprint/validation đầy đủ.
- [ ] Tiptap toolbar + mount/unmount + Save/Preview/Publish UI và feedback lỗi/conflict.
- [ ] Scheduled edit cho PUBLISHED draft, hủy/đổi lịch, chống job cũ và timezone tests.
- [ ] Media access chung cho page/design/template/settings/avatar; PLATFORM media allowlist.
- [ ] Upload quota reservation tính cả UPLOADING, provider-response validation, orphan reconcile.
- [ ] Media library/selector/reuse/alt/delete; reference collectors public/draft/global; destroy job/retry và race publish-delete.
- [ ] SEO/OG/canonical/robots/sitemap, taxonomy search, preview noindex/no-store.

### 4. Template/design

- [x] GrapesJS module, ba template seed, design publish/fingerprint cơ bản.
- [ ] Platform template CRUD/publish/version/categories và builder UI.
- [ ] GrapesJS component/schema registry đầy đủ: validator hiện nhìn pages.sections, chưa kiểm tra frames/components/styles thật.
- [ ] Trusted rules, media/binding/menu/route validation, đúng một homepage.
- [ ] Owner customization theo rules server; upgrade template không tự đổi public.
- [ ] Public renderer dùng published_data thành sections/theme/menu thực tế; hiện public dùng layout chung.
- [ ] Authorized preview và browser editor lifecycle tests.

### 5. Website và vận hành nghiệp vụ

- [x] Landing/catalog/blog/post/page, pagination cơ bản.
- [ ] Search/filter topic/template, thumbnails, public settings projection.
- [ ] Settings singleton form/schema/fingerprint/cache invalidation.
- [ ] Report create/review/moderation, tenant target checks/rate limit/audit.
- [ ] Custom notification channel đúng recipient_user_id, UUID/read/unread/email queue.
- [ ] Audit UI append-only và security events cho identity/access.
- [ ] DNS verification/domain resolver contract + fake tests; giữ feature flag tắt đến nghiệm thu.

### 6. Release và nghiệm thu

- [x] Docker app/worker/scheduler, Nginx, MySQL/Redis; local DB ports chỉ localhost.
- [x] Production không publish DB/Redis, Nginx localhost; ép debug false/production/secure session.
- [x] Security headers, audit canonical columns, scheduled audit actor.
- [x] CI build assets trước feature tests; lint/format/build/dependency audit.
- [ ] Failed jobs persistence: config trỏ failed_jobs nhưng thiếu migration hạ tầng.
- [ ] Nginx assets cùng immutable release: hiện bind source host còn PHP chạy image.
- [ ] Scheduler heartbeat, readiness DB/Redis, queue alerts/log rotation, backup restore drill.
- [ ] MySQL least-privilege user, production secrets, HTTPS/trusted proxy/HSTS tại TLS edge.
- [ ] CSP còn unsafe-inline/unsafe-eval; siết theo từng surface sau browser compatibility tests.
- [ ] PHPStan/Larastan; MySQL race tests; E2E 2 account toàn luồng mục 20 prompt.
- [ ] Cloudinary/SMTP/DNS/VPS live và production image smoke sau lần sửa này.

## Lỗi sửa trong lượt review

1. Signed email verification luôn đổi status ACTIVE: nay chỉ chuyển PENDING_VERIFICATION bằng conditional update, không mở khóa SUSPENDED.
2. Action create/upload/publish bỏ qua locked_until: dùng canAccessApplication; chặn upload/publish blog suspended/deleted.
3. Publish array content_json gây lỗi Query Builder: encode trước update, validate media trên snapshot merged để không bỏ qua media có sẵn.
4. Scheduler có thể đăng soft-deleted content/blog/owner, owner chưa verify/đang khóa: lọc candidate và kiểm tra lại trong transaction.
5. Renderer đọc READY media đã soft-delete: lọc deleted_at.
6. Slug từ tên rỗng/trùng gây SQL error: validation trước insert; concurrent unique race còn cần handling riêng.
7. Quota create thiếu điểm khóa ổn định: khóa owner row trước count; race test chưa chạy.
8. Worker timeout 90s bằng retry_after 90s: nâng Redis retry_after mặc định 120s, dispatch after commit.
9. GrapesJS debounce chạy sau unmount: clear timeout trước destroy.
10. CI feature tests trước Vite build trên checkout sạch: build trước test.

## Bằng chứng kiểm chứng

- Regression MySQL: 16 tests/33 assertions passed, có verification không mở khóa, JSON draft publish và scheduler skip lock/deletion.
- Dependency audit không có advisory; phạm vi này không bao gồm Docker OS vulnerability scan.
- Không migrate:fresh DB ứng dụng, không deploy VPS. Runtime Docker chưa tự nhận mã PHP mới nếu chưa rebuild image.
- Các checkbox trống cần code/test thật; không tính scaffold hoặc chỉ có bảng DB là hoàn thành.

Ưu tiên tiếp theo: identity/session/2FA → Filament tenant/content/media → template/design renderer → settings/moderation/notifications/domain → browser/race/release.
