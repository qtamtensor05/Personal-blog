# Đối chiếu DBML và migration

- Nguồn chuẩn: `blog_platform_complete.dbml`, `blog_platform_mysql.sql`, manifest và validation report trong thư mục này.
- Migration `2026_10_11_000000_create_blog_platform_schema.php` thực thi nguyên DDL chuẩn trên database sạch: 29 business tables, 59 foreign keys và 12 enum.
- DDL tạo toàn bộ bảng trước rồi thêm FK nên xử lý được vòng `users.avatar_media_id` / `media_assets.uploaded_by`.
- Không thêm jobs/cache/comments/members/follows/revision tables vào tập bảng nghiệp vụ.
- Migration chỉ hỗ trợ MySQL 8.0.16+ để CHECK/JSON/FK có semantics đúng; không tuyên bố SQLite tương đương.
- `down()` cố ý không phá dữ liệu. Rollback production dùng previous image và database backup.
