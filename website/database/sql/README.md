# SQL dùng khi chạy ứng dụng

Schema ban đầu được tạo trực tiếp bằng các Laravel migration trong
`database/migrations`; ứng dụng không còn nạp file SQL để tạo bảng.

`seed_roles_permissions.sql` vẫn được Git quản lý và được `PlatformSeeder`
dùng để tạo role/permission nền tảng.

ERD và các bản SQL tham chiếu trong `docs/database/` chỉ phục vụ tài liệu,
không phải dependency runtime của migration.
