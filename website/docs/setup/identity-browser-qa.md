# Nghiệm thu browser mục 1

Trạng thái: chưa thực hiện trong phiên agent vì inventory computer-use không có browser. Các bước dưới là kịch bản nghiệm thu, không phải kết quả đã đạt.

## Chuẩn bị local

Chạy theo README, dùng Mailpit. Chuẩn bị hai tài khoản thử nghiệm và hai phiên browser độc lập (ví dụ Chrome thường và cửa sổ ẩn danh). Không dùng tài khoản/dữ liệu production. Với luồng quyền quản trị, cần hai Super Admin đã xác minh, có authenticator riêng. Không ghi mật khẩu, OTP, QR secret hoặc recovery code vào ảnh/log báo cáo.

## Hồ sơ và phiên

1. Đăng nhập cùng tài khoản thử nghiệm ở cả hai browser, một phiên chọn ghi nhớ đăng nhập. Mở /account, sửa tên; tải lại để xác nhận lưu. Thử tên rỗng để xác nhận lỗi form.
2. Đổi mật khẩu với mật khẩu hiện tại sai: bị từ chối, dữ liệu không đổi. Đổi đúng: phiên hiện tại về login, browser thứ hai không truy cập /workspace khi tải lại. Mật khẩu cũ không đăng nhập được; mật khẩu mới dùng được.
3. Đổi email trùng: bị từ chối. Đổi email mới: tới trang xác minh, browser thứ hai bị đăng xuất. Liên kết xác minh của email cũ bị chặn; liên kết mới trong Mailpit cho phép quay lại workspace. Đảm bảo lựa chọn mẫu đang giữ không bị mất nếu có.
4. Yêu cầu reset, thử token sai/hết hạn/đã dùng; thông báo lỗi không lộ tài khoản có tồn tại. Reset thành công thu hồi các phiên cũ.

## 2FA và quyền quản trị

1. Đăng nhập Platform Admin chưa enroll: phải hoàn tất thiết lập 2FA trước khi mở màn hình quản trị. Lưu recovery code an toàn, không đưa vào báo cáo.
2. Đăng xuất rồi đăng nhập lại: mật khẩu đúng chưa đủ; OTP sai bị từ chối, OTP đúng vào được panel. Đăng nhập portal rồi mở platform không bỏ qua bước login/challenge riêng.
3. Thử recovery code ở phiên mới: dùng được một lần; dùng lại bị chặn. Kiểm tra thao tác regenerate recovery qua profile và bắt buộc 2FA không thể tự bị tắt.
4. Tại /platform-admin/access-management: tạo/sửa vai trò tùy chỉnh, gán cho tài khoản thử nghiệm; chỉ các màn hình đúng quyền xuất hiện và endpoint trái quyền trả 403. Thu hồi role khiến phiên tài khoản đó bị thu hồi.
5. Role hệ thống không sửa/xóa được; role còn lượt gán không xóa được; không tự thay grant; không thu hồi Super Admin khả dụng cuối cùng. Super Admin cuối cùng không đổi email đến khi có người thứ hai khả dụng.
6. Khóa rồi mở lại user thường: browser với cookie ghi nhớ cũ không tự đăng nhập trở lại. Platform Admin không khóa tài khoản admin khác qua moderation user thường.

## Trình bày kết quả

Kiểm tra thêm bàn phím, nhãn/lỗi/focus ở 360/768/1024/1440px. Ghi từng bước PASS/FAIL, browser/phiên bản, URL, bước tái hiện và ảnh đã che thông tin nhạy cảm. Chỉ đóng 1.1b và 1.3 browser sau khi có bằng chứng; test PHP/HTTP không thay kết quả browser.

## Test cạnh tranh tự động

Tại website/, dùng cấu hình MySQL test trong phpunit.xml:

```powershell
php artisan test --testsuite=Concurrency
```

Suite reset schema **DB *_test** để fixture được commit và nhìn thấy từ hai tiến trình. Không chạy cùng lúc với suite khác trên cùng DB. Cần quyền đọc information_schema.innodb_trx để xác nhận LOCK WAIT (CI/local hiện dùng root). Thời gian chờ có giới hạn; không có skip âm thầm. DB ứng dụng không được dùng cho suite này.
