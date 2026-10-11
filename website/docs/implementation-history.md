# Lịch sử triển khai

## 2026-10-11

- Hoàn thiện mục 1 bằng suite Concurrency: hai tiến trình PHP/kết nối MySQL, barrier và kiểm chứng INNODB_TRX LOCK WAIT; bốn kịch bản bảo vệ Super Admin/recovery code đã đạt 4 tests / 31 assertions. phpunit.xml đưa suite vào full run/CI; chỉ cho phép reset DB *_test, không chạy đồng thời suite khác trên cùng DB.
- Test restriction lifecycle phát hiện remember_token cũ chưa bị thu hồi khi khóa/mở user; đã sửa. Đồng thời sửa audit moderation truyền null cho tham số array gây rollback. Thêm kịch bản browser thủ công tại docs/setup/identity-browser-qa.md; computer-use inventory vẫn apps=[]/browsers=[].
- Full suite sau các bản sửa đạt 45 tests / 215 assertions; format/lint/build/Pint đạt. Docker local rebuild và restart Nginx, Platform login HTTP 200. Không deploy hoặc migration/seed DB ứng dụng; DB test được reset có kiểm tra an toàn.
- Mục 1 Identity/Access: thêm /account (tên, mật khẩu hiện tại khi đổi mật khẩu/email, xác minh email mới, revoke sessions/remember/reset tokens và audit); auth provider bỏ qua soft-deleted user ở mọi lookup. Platform profile giữ MFA nhưng credential đi qua Action chung.
- Thêm /platform-admin/access-management: custom roles và grant/revoke cho tài khoản có sẵn, quyền kiểm tra tại Query/Action, khóa role theo thứ tự ổn định, chặn tự đổi grant/nâng quyền vượt actor/sửa role hệ thống/mất Super Admin cuối cùng (kể cả email change). Không thay schema hoặc seed DB ứng dụng.
- Đợt kiểm tra mục 1: full suite cuối đạt 40 tests / 180 assertions, bao gồm challenge sai/đúng; format/lint/build đạt và Docker local rebuild thành công. Browser inventory vẫn rỗng; browser enroll/recovery và concurrency nhiều connection còn mở trong checklist.
- Sửa CI checkout sạch thiếu SQL sau khi ignore docs/database: chuyển runtime schema và platform seed sang database/sql được Git quản lý, cập nhật migration/seeder và thêm CI preflight kiểm tra file đã tracked. Không thay schema hoặc chạy migration trên DB ứng dụng.
- Kiểm chứng sửa CI tại local: PublicIsolationTest + InterfaceFlowsTest đạt 14 tests / 66 assertions trên DB test, Pint đạt. Chưa chạy GitHub Actions remote; cần commit cả database/sql và workflow.
- Sửa chữ trắng trên nền trắng trong Blog Studio: Filament dark mode kế thừa màu chữ trong khi surface tùy chỉnh luôn sáng. Cố định hai panel light mode theo thiết kế, khai báo màu chữ cho vùng quản lý/tab/editor toolbar. Test PanelThemeTest đạt 1 test / 2 assertions; không thay đổi dữ liệu blog.
- Tiếp tục hai UI tham chiếu: gallery/auth/chọn mẫu và Platform Admin quản lý mẫu; nối snapshot draft/live, Blog Studio và renderer, phân quyền/tenant isolation; xem `ui-implementation-handoff.md`.
- Regression hiện tại đạt 29 test / 97 assertion; Docker local rebuild đồng bộ PHP/Nginx và publish Filament assets, kiểm tra HTTP route/assets đạt. Không chạy migration/seed trên DB ứng dụng hoặc deploy production; browser không khả dụng nên visual QA còn mở.

- Rà soát nghiệm thu: thêm `project-completion-checklist.md`, phân biệt MVP và yêu cầu chưa hoàn thành.
- Sửa email verification không được kích hoạt lại tài khoản suspended; bổ sung kiểm tra locked/deleted/suspended trong publish, upload và scheduler.
- Sửa lưu JSON draft khi publish, kiểm tra media trên snapshot hợp nhất, khóa owner khi kiểm tra quota tạo blog, cấu hình Redis queue và cleanup editor.
- Sửa CI build assets trước test; bổ sung regression test, đạt 16 test / 33 assertion. Chưa rebuild/deploy runtime trong lần rà soát này.

- Giai đoạn 1: audit Laravel 13/PHP 8.4; sao chép ERD chuẩn; migration/seed; auth và access guard; Docker baseline.
- Giai đoạn 2: tạo/list blog, quota, owner isolation và public path resolver.
- Giai đoạn 3-4: post/design publish actions, JSON fingerprint, safe section validation, Tiptap và GrapesJS integration modules, ba template demo khác nhau.
- Giai đoạn 5-6: landing/catalog/public render, audit contract, production compose/runbook và risk-focused unit/tenant tests.
- Kiểm tra thực tế và blocker được ghi trong `current-state.md`; không ghi nhận Cloudinary/SMTP/VPS là đã tích hợp live.
- Gia cố bảo mật: đóng cổng dữ liệu trong production, ép production environment, tenant-scope media, sửa audit/scheduler, khóa `locked_until`, kiểm tra upload và thêm HTTP security headers.
