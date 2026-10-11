# Gia cố xác thực và thu hồi phiên

## Cơ chế

- Xác minh email: khóa user, đối chiếu hash trong liên kết với email hiện tại rồi mới xác minh và chuyển `PENDING_VERIFICATION` sang `ACTIVE` trong cùng transaction.
- Reset mật khẩu: khóa danh tính theo email trước khi broker kiểm tra, thực thi callback và tiêu thụ token. Đổi email cũng khóa user; request dùng email/token cũ phải kiểm tra lại sau khi chờ khóa.
- Đổi email/mật khẩu: xóa token qua broker mặc định đang cấu hình (`Password::deleteToken`), không dùng tên bảng cố định.
- Phiên đăng nhập: gắn dấu HMAC theo ID, password hash và remember token. Đổi quyền/mật khẩu/email hoặc moderation xoay remember token để thu hồi phiên. Middleware kiểm tra dữ liệu user hiện tại trước và sau request ở web và cả hai panel. Phiên cũ bị ghi lại bởi request đang chạy vẫn không thể xác thực ở request tiếp theo.
- Vai trò chỉ có `platform.roles.manage` hoặc `platform.admins.manage` được vào trang quản lý quyền; dashboard vẫn yêu cầu `platform.dashboard.view`. Yêu cầu MFA không thay đổi.
- Auth provider chỉ thêm điều kiện `deleted_at IS NULL` khi bảng model có cột đó. Các workflow nghiệp vụ vẫn dùng model User/schema của dự án; đây không phải cam kết hỗ trợ thay toàn bộ schema bằng model tùy ý.

## Lưu ý vận hành

Các phiên đăng nhập được tạo trước bản cập nhật không có dấu HMAC và phải đăng nhập lại. Không cần migration. Cơ chế kiểm tra không phụ thuộc nơi lưu session (database, file, Redis, cookie hoặc array); xóa bảng sessions chỉ là dọn dẹp bổ sung. Request đã thực hiện tác vụ trước thời điểm thu hồi không thể bị hoàn tác bằng middleware.

Remember token hiện đóng vai trò mốc thu hồi chung: mọi luồng xoay token, bao gồm logout chuẩn của Laravel, cũng làm các phiên khác phải đăng nhập lại. Nếu sau này cần logout chỉ một thiết bị nhưng giữ các thiết bị còn lại, cần mốc thu hồi riêng thay vì dùng chung remember token.

Filament hiện tại tự đọc user mới bằng `lockForUpdate()` trước khi kiểm tra recovery code. Không chỉnh sửa vendor; test concurrency cố ý tải actor trước khi chờ khóa để bảo vệ trường hợp stale model.

## Kiểm tra

Chạy tuần tự trên database riêng có hậu tố `_test`:

```sh
php vendor/bin/pint --test
php artisan test
```

Trên Windows nếu PHP chưa bật intl: `php -d extension=intl vendor/pestphp/pest/bin/pest`.
Suite concurrency dùng hai tiến trình/kết nối MySQL và xác nhận trạng thái `LOCK WAIT`, không chỉ gọi tuần tự. Suite này chạy `migrate:fresh` trên database test, tuyệt đối không trỏ vào database ứng dụng.

### Kết quả local ngày 11/10/2026

- Full Pest: 53 tests, 249 assertions, PASS; bao gồm 6 test concurrency. Lần chạy riêng concurrency: 6 tests, 45 assertions, PASS.
- Test phiên dùng driver array/file, bao gồm replay dấu phiên cũ và thu hồi trong request; chưa kiểm tra trực tiếp driver Redis/cookie bằng browser.
- `npm run format:check`, `npm run lint`, `npm run build`, `docker compose config --quiet`, `git diff --check`: PASS. Build có cảnh báo chunk lớn và optional fontaine, không thất bại.
- Không deploy, không chạy migration trên database ứng dụng, không có kết quả browser QA mới trong đợt này.
