# SQL bắt buộc để chạy ứng dụng

Hai file SQL trong thư mục này được Git quản lý và đóng vào Docker image:

- `blog_platform_mysql.sql`: migration tạo schema ban đầu trên MySQL.
- `seed_roles_permissions.sql`: PlatformSeeder tạo role/permission nền tảng.

Giữ SQL runtime tại đây để migration/seed có đầu vào ổn định. ERD và bản tham chiếu trong `docs/database/` cũng được quản lý trong Git, nhưng không phải dependency runtime.

Đây là đầu vào cài mới. Thay đổi schema của database đã chạy phải qua migration bổ sung; không sửa SQL ban đầu rồi chạy lại để nâng cấp production. Không có tài khoản/mật khẩu admin mặc định trong seed này.
