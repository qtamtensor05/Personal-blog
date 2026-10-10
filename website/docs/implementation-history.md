# Lịch sử triển khai

## 2026-10-11

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
