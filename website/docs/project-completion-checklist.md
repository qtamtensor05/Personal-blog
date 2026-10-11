# Checklist hoàn thành và phân công

Cập nhật: 2026-10-11. Đối chiếu source hiện tại, [đặc tả UI](DESIGN_REQUIREMENTS_BLOG_PLATFORM.md), [bàn giao UI](ui-implementation-handoff.md) và [lịch sử](implementation-history.md). Đây là checklist hiện hành, thay bản baseline trước đợt triển khai UI.

## Cách đọc và giao việc

- [x] Đã triển khai phần việc được mô tả, có bằng chứng source/test hoặc smoke tương ứng; không đồng nghĩa nghiệm thu production.
- [ ] Phần còn phải thực hiện hoặc kiểm chứng. Nhãn **QA** nghĩa là đã có code, không giao làm lại từ đầu.
- Phân công theo mục 1–6; mỗi mã 1.1, 2.1… là đầu việc chi tiết trong mục tương ứng. Chỉ đóng việc khi có bằng chứng đáp ứng tiêu chí ghi ngay sau đầu việc.

## Các mục và phụ thuộc

| Mục | Phụ thuộc trước khi tích hợp | Đầu ra bàn giao |
| --- | --- | --- |
| 1. Nền tảng / identity | Không; nền chung | Auth/quyền/session và test bảo mật |
| 2. Blog / tenant | Guard/quyền từ 1 | Onboarding/settings và tenant isolation |
| 3. Content / media | OwnerContext/quota từ 2, guard từ 1 | Viết–lưu–preview–đăng và media an toàn |
| 4. Template / design | Quyền từ 1; snapshot từ 2; media contract từ 3 | Builder, snapshot và renderer nhất quán |
| 5. Website / vận hành nghiệp vụ | Published content/design từ 3–4 | Public UI, settings và workflow P1 |
| 6. Release / nghiệm thu | CI làm xuyên suốt; phát hành sau tích hợp 1–5 | Báo cáo E2E, vận hành và release gate |

Thứ tự: ổn định 1 → 2 → làm 3 và 4 song song → tích hợp 5 → nghiệm thu 6. Không cần đợi hoàn thành mọi việc của một mục: chỉ cần phụ thuộc trực tiếp đã sẵn sàng. Mục 6 bắt đầu CI/test ngay từ đầu, không dồn kiểm thử về cuối.

Trước khi làm song song, thống nhất OwnerContext/blog_id, JSON draft/live và fingerprint, hợp đồng media/preview, mã lỗi/phân quyền. Mục 3 sở hữu media Action/Query; mục 4–5 tái sử dụng, không tạo đường kiểm tra tenant riêng.

## 1. Nền tảng, identity/access

- [x] Stack/lockfiles, migration/seed, session login/logout/register, email verification, CSRF/throttle cơ bản; schema đối chiếu trước đây là 29 bảng/59 FK.
- [x] Password reset qua broker, đổi remember_token và thu hồi sessions; bootstrap admin, required 2FA và chặn portal session bỏ qua platform login.
- [x] Guard ACTIVE/verified/locked_until/deleted_at tại canAccessApplication; moderation tài khoản thường có restriction lifecycle, session revoke và audit.
- [ ] **1.1 — QA:** Test reset token sai/hết hạn/đã dùng, session cũ bị thu hồi; browser enroll/challenge/recovery 2FA và portal→platform không bypass. Đạt khi có test/báo cáo cho từng nhánh.
- [ ] **1.2 — Triển khai:** Profile người dùng, đổi mật khẩu khi đã đăng nhập, đổi email và xác minh lại; test quyền, validation, session lifecycle. Không gộp với password reset đã có.
- [ ] **1.3 — Xác nhận phạm vi:** CRUD role/admin và bảo vệ Super Admin cuối cùng nếu đưa vào phạm vi release. Chưa có workflow đầy đủ; cần chốt với đặc tả, không suy từ bảng DB rằng phải dựng thêm màn hình.

## 2. Blog và tenant

- [x] Create/list/detail owner-only, khóa owner khi kiểm tra quota; onboarding giữ mẫu qua auth, template PUBLISHED và snapshot độc lập.
- [x] Selector blog trong Studio, OwnerContext phía server, settings tên/visibility/SEO/media; private reader hiện chỉ Owner hợp lệ.
- [ ] **2.1 — QA:** E2E hai tài khoản: tạo blog, chuyển selector, sửa settings, giả mạo blog/content/media ID; tài khoản B không đọc/sửa nháp A.
- [ ] **2.2 — Triển khai + test:** Hoàn thiện các trường/settings/slug theo đặc tả chưa được form hỗ trợ; xử lý unique collision thành lỗi form. Test quota/slug bằng nhiều connection, không chỉ tuần tự.
- [ ] **2.3 — QA:** PUBLIC/UNLISTED/PRIVATE và hidden/suspended/deleted đúng ở discovery, URL trực tiếp và preview. Không thêm member/moderator hoặc support bypass khi chưa được yêu cầu.

## 3. Content/media

- [x] Studio bài/trang/danh mục/thẻ, Tiptap, lưu draft/fingerprint, publish/schedule/archive và preview có auth/noindex/no-store.
- [x] Tách sửa draft của bài live; scheduler áp dụng draft đã hẹn; test hủy lịch khi lưu draft để tránh CHECK constraint.
- [x] Cloudinary adapter, validation ảnh, quota reservation gồm UPLOADING/DELETING; library/picker/alt/delete có kiểm tra tham chiếu và tenant scope.
- [ ] **3.1 — QA:** Browser Tiptap mount/unmount, toolbar, giữ nội dung khi lỗi/conflict, Save/Preview/Publish. Không yêu cầu autosave giả; chỉ thêm autosave khi có concurrency test.
- [ ] **3.2 — QA + bổ sung:** Test đổi/hủy lịch, job cũ, bài live sửa nháp, timezone và biên thời gian; UI hiện nhập UTC, phải hướng dẫn rõ hoặc chốt chuyển đổi.
- [ ] **3.3 — Triển khai + test:** Kiểm chứng provider-response validation, orphan reconciliation; destroy/retry và race publish-delete/upload-quota bằng nhiều connection. Có Action delete không đồng nghĩa đã có destroy job hoàn chỉnh.
- [ ] **3.4 — Triển khai:** Upload PLATFORM cho admin, quyền/allowlist/quota và picker; hiện PLATFORM media chỉ đọc/chọn asset có sẵn. Rà lại media references page/design/settings/avatar nếu được dùng.
- [ ] **3.5 — Hoàn thiện + QA:** Kiểm kê SEO/OG/canonical/robots/sitemap theo đặc tả; taxonomy search và protected preview đã có, không làm lại. Đạt khi URL public/preview và metadata có test tương ứng.

## 4. Template/design

- [x] Platform gallery dữ liệu thật, lọc/tìm/phân trang, tạo/sửa/lưu nháp/publish/hide, chủ đề, preview và thumbnail PLATFORM.
- [x] GrapesJS constrained sections/traits/device switch, native project + projection; registry kiểm tra frames/components và chặn script.
- [x] Owner draft design/menu, trusted source rules, fingerprint, snapshot nguồn độc lập; public renderer dùng published_data/sections/theme/menu, không tự cập nhật theo mẫu.
- [ ] **4.1 — QA:** Browser thêm/sắp xếp/sửa section, save/reload, chuyển mẫu, preview/live, cảnh báo rời trang; output sau reload khớp JSON đã lưu.
- [ ] **4.2 — Hoàn thiện + test:** Rà coverage schema registry/frames/styles, binding/media/menu/route, đúng một homepage; server từ chối input giả mạo. Không coi allowlist hiện có là coverage mọi tổ hợp.
- [ ] **4.3 — Triển khai:** Bổ sung control font/spacing/logo còn thiếu theo đặc tả; kiểm tra responsive. Không mở HTML/JS tùy ý.
- [ ] **4.4 — Chốt phạm vi:** Nâng cấp template tương thích nếu cần cho release; luôn giữ blog live độc lập. Không tạo hệ thống lịch sử phiên bản.

## 5. Website và vận hành nghiệp vụ

- [x] Gallery/preview/auth, discovery tìm/lọc chủ đề, hướng dẫn, thumbnail thực; public settings projection allowlist.
- [x] Settings theo nhóm JSON có validation/quyền/transaction, bảo toàn nhóm khác; topic và moderation/audit/notifications có nền query/UI.
- [ ] **5.1 — QA:** Hai UI và Studio ở 360/768/1024/1440px, keyboard/focus/contrast, menu active/hover, empty/loading/error. Test hồi quy theme đã có; chưa thay thế visual QA.
- [ ] **5.2 — Hoàn thiện:** Settings stale-write/fingerprint và chiến lược cache nếu dùng cache; hai phiên sửa cùng trường không âm thầm ghi đè.
- [ ] **5.3 — P1:** Report create/review/detail, tenant target/rate limit/reason/audit và filters; moderation Action có sẵn, cần hoàn chỉnh workflow.
- [ ] **5.4 — P1:** Notifications đúng recipient/UUID, read/unread/detail/email queue; audit detail/filter/append-only và coverage identity security events. Projection/list hiện tại chưa đủ nghiệm thu workflow.

## 6. Release và nghiệm thu

- [x] Docker app/web/worker/scheduler, DB/Redis localhost ở local và đóng cổng production; production guard/security headers.
- [x] PHP/Nginx dùng public assets cùng release, image chạy filament:assets; đã rebuild và smoke HTTP local.
- [x] CI build assets trước test; có lint/format/build/audit và hướng dẫn local/test/VPS.
- [ ] **6.1 — Triển khai + test:** Failed-job persistence (config dùng failed_jobs, chưa có migration hạ tầng tương ứng); thử một job lỗi và xác minh retry/failed record.
- [ ] **6.2 — Vận hành:** Scheduler heartbeat, readiness DB/Redis, queue alerts/log rotation, backup/restore drill có bằng chứng; không chỉ viết runbook.
- [ ] **6.3 — Cấu hình production:** MySQL least privilege, production secrets/HTTPS/trusted proxy/HSTS; giữ APP_KEY ổn định. Không deploy nếu chưa được phép.
- [ ] **6.4 — Kiểm tra bảo mật:** Siết CSP theo surface sau kiểm tra browser (hiện còn unsafe-inline/unsafe-eval); static analysis và concurrency tests theo rủi ro.
- [ ] **6.5 — Nghiệm thu release:** Checkout sạch/cài mới theo README, E2E hai account tạo–viết–xuất bản–đọc, responsive/accessibility và editor lifecycle; chạy lại full suite tại revision bàn giao.
- [ ] **6.6 — Provider/hạ tầng:** Cloudinary/SMTP thật và production image smoke trên môi trường được cấp quyền. Thiếu credentials chỉ chặn kiểm chứng live, không chặn unit/integration fake.

## Ngoài phạm vi hiện tại

Không đưa vào phân công mặc định: Comment, Invitation/Member/Follow/Moderator, daily post stats, social login, custom domain/DNS automation, billing, lịch sử phiên bản, phân mảnh bảng content/media. Một blog một Owner. Các mục domain có trong baseline cũ được loại khỏi backlog hiện hành theo đặc tả mới, không phải đánh dấu đã hoàn thành.

## Bằng chứng và giới hạn

- Lần kiểm tra UI trước: full suite **29 tests / 97 assertions**; format/lint/build và Composer/npm audit đạt. Xem bàn giao UI để biết phạm vi.
- Lần sửa theme tiếp theo: **PanelThemeTest 1 test / 2 assertions**, Pint/Prettier đạt; Docker rebuild và HTTP login xác nhận light mode. Chưa chạy lại full suite gộp sau khi thêm test theme, không cộng thành kết quả full-suite mới.
- Browser của phiên trước không khả dụng; responsive/keyboard/contrast, drag/drop và browser 2FA chưa nghiệm thu. HTTP 200 không chứng minh tương tác browser đúng.
- Số bảng/FK là bằng chứng lần kiểm tra schema trước, không phải migration mới trong lần cập nhật tài liệu.
- Lần cập nhật checklist này chỉ rà source/tài liệu, không chạy lại test/build, không migration/seed/deploy. Không biến ô đã có code thành cam kết production.
- Các lỗi đã sửa và kết quả từng lượt lưu tại implementation-history.md; checklist này quản lý công việc hiện tại, không thay thế lịch sử.

## Đợt công việc đề xuất tiếp theo

1. QA P0: 1.1, 2.1, 2.3, 3.1–3.2, 4.1, 5.1; ghi issue tái hiện cụ thể, ưu tiên lỗi mất dữ liệu/tenant/auth.
2. Triển khai song song: 2.2, 3.3–3.4, 4.2–4.3; 1.2 và 3.5 theo mức ưu tiên đã chốt.
3. P1: 5.2–5.4 sau khi luồng tạo–viết–xuất bản–đọc ổn định.
4. Release gate: 6.1–6.6; chốt phạm vi 1.3/4.4 trước khi ước lượng, không tự thêm tính năng ngoài yêu cầu.
