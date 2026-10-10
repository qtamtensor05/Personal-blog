-- Multi-Tenant Blog Platform | MySQL 8.0.16+ / InnoDB / utf8mb4
-- Run against a NEW empty database selected by the operator. No DROP, no FK disable.
-- Laravel default migrations must be adapted; do not create users/sessions twice.
SET NAMES utf8mb4;

-- Tài khoản chung. User là mặc định; quyền quản trị cấp qua user_platform_roles; quyền Owner suy ra từ blogs.owner_user_id.
CREATE TABLE `users` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(160) NOT NULL,
  `email` VARCHAR(254) NOT NULL,
  `email_verified_at` DATETIME NULL,
  `password` VARCHAR(255) NOT NULL,
  `status` ENUM('PENDING_VERIFICATION','ACTIVE','LOCKED','SUSPENDED','DISABLED') NOT NULL DEFAULT 'PENDING_VERIFICATION',
  `locked_until` DATETIME NULL,
  `last_login_at` DATETIME NULL,
  `remember_token` VARCHAR(100) NULL,
  `avatar_media_id` BIGINT UNSIGNED NULL,
  `two_factor_secret` TEXT NULL,
  `two_factor_recovery_codes` TEXT NULL,
  `two_factor_confirmed_at` DATETIME NULL,
  `deleted_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `u_0` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- SUPER_ADMIN và PLATFORM_ADMIN. Chủ blog xác định bằng blogs.owner_user_id.
CREATE TABLE `platform_roles` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(60) NOT NULL,
  `name` VARCHAR(160) NOT NULL,
  `description` TEXT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `u_0` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Quyền platform.* cho quản trị nền tảng. Owner dùng Policy kiểm tra blogs.owner_user_id; không có RBAC thành viên blog.
CREATE TABLE `permissions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(120) NOT NULL,
  `scope` VARCHAR(20) NOT NULL,
  `description` TEXT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `u_0` (`code`),
  CONSTRAINT `ck_permissions_0` CHECK (scope = 'PLATFORM')
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Quyền của vai trò nền tảng.
CREATE TABLE `platform_role_permissions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `role_id` BIGINT UNSIGNED NOT NULL,
  `permission_id` BIGINT UNSIGNED NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `u_0` (`role_id`,`permission_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Gán quyền quản trị nền tảng. User thường không cần bản ghi tại đây.
CREATE TABLE `user_platform_roles` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `role_id` BIGINT UNSIGNED NOT NULL,
  `granted_by` BIGINT UNSIGNED NOT NULL,
  `expires_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `u_0` (`user_id`,`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Database session driver Laravel. Thu hồi bằng xóa session; last_activity là Unix timestamp.
CREATE TABLE `sessions` (
  `id` VARCHAR(255) NOT NULL,
  `user_id` BIGINT UNSIGNED NULL,
  `ip_address` VARCHAR(45) NULL,
  `user_agent` TEXT NULL,
  `payload` LONGTEXT NOT NULL,
  `last_activity` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  KEY `i_0` (`user_id`),
  KEY `i_1` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Theo cấu trúc password broker Laravel. Token được hash và có TTL ở cấu hình.
CREATE TABLE `password_reset_tokens` (
  `email` VARCHAR(254) NOT NULL,
  `token` VARCHAR(255) NOT NULL,
  `created_at` DATETIME NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Lịch sử khóa/đình chỉ/vô hiệu hóa; đồng bộ users.status trong transaction. Mở khóa không mặc định ACTIVE nếu email chưa xác thực.
CREATE TABLE `account_restrictions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `kind` ENUM('LOCK','SUSPEND','DISABLE') NOT NULL,
  `status` ENUM('ACTIVE','REVOKED','EXPIRED') NOT NULL DEFAULT 'ACTIVE',
  `reason` TEXT NOT NULL,
  `issued_by` BIGINT UNSIGNED NOT NULL,
  `starts_at` DATETIME NOT NULL,
  `ends_at` DATETIME NULL,
  `revoked_by` BIGINT UNSIGNED NULL,
  `revoked_at` DATETIME NULL,
  `revoke_reason` TEXT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `i_0` (`user_id`,`status`,`ends_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Append-only. Không lưu password, OTP, token, recovery code. actor/subject nullable cho thất bại đăng nhập.
CREATE TABLE `security_audit_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `actor_user_id` BIGINT UNSIGNED NULL,
  `subject_user_id` BIGINT UNSIGNED NULL,
  `event` VARCHAR(120) NOT NULL,
  `outcome` VARCHAR(20) NOT NULL,
  `ip_address` VARCHAR(45) NULL,
  `user_agent` TEXT NULL,
  `context` JSON NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `i_0` (`subject_user_id`,`created_at`),
  KEY `i_1` (`event`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Chủ đề blog trên Main Website; khác danh mục bài viết trong từng blog.
CREATE TABLE `blog_topics` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(160) NOT NULL,
  `slug` VARCHAR(160) NOT NULL,
  `is_active` BOOLEAN NOT NULL DEFAULT 1,
  `created_by` BIGINT UNSIGNED NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `u_0` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tenant gốc, một chủ sở hữu qua owner_user_id. Không có thành viên, lời mời hoặc vai trò Editor. Trạng thái và visibility độc lập.
CREATE TABLE `blogs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `owner_user_id` BIGINT UNSIGNED NOT NULL,
  `name` VARCHAR(160) NOT NULL,
  `slug` VARCHAR(160) NOT NULL,
  `description` TEXT NULL,
  `status` ENUM('DRAFT','PUBLISHED','HIDDEN','SUSPENDED','ARCHIVED') NOT NULL DEFAULT 'DRAFT',
  `visibility` ENUM('PUBLIC','UNLISTED','PRIVATE') NOT NULL DEFAULT 'PUBLIC',
  `discoverable` BOOLEAN NOT NULL DEFAULT 1,
  `published_at` DATETIME NULL,
  `deleted_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `u_0` (`slug`),
  KEY `i_0` (`owner_user_id`,`status`),
  KEY `i_1` (`status`,`visibility`,`discoverable`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Phân loại blog trong thư viện khám phá.
CREATE TABLE `blog_topic_assignments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `blog_id` BIGINT UNSIGNED NOT NULL,
  `topic_id` BIGINT UNSIGNED NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `u_0` (`blog_id`,`topic_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 1:1 cài đặt riêng mỗi blog; JSON cho liên kết xã hội và giới hạn do nền tảng cấp.
CREATE TABLE `blog_settings` (
  `blog_id` BIGINT UNSIGNED NOT NULL,
  `locale` VARCHAR(20) NOT NULL DEFAULT 'vi',
  `timezone` VARCHAR(80) NOT NULL DEFAULT 'Asia/Ho_Chi_Minh',
  `logo_media_id` BIGINT UNSIGNED NULL,
  `favicon_media_id` BIGINT UNSIGNED NULL,
  `posts_per_page` INT UNSIGNED NOT NULL DEFAULT 10,
  `social_links` JSON NULL,
  `seo_title` VARCHAR(255) NULL,
  `seo_description` VARCHAR(500) NULL,
  `og_media_id` BIGINT UNSIGNED NULL,
  `storage_quota_bytes` BIGINT UNSIGNED NOT NULL DEFAULT 104857600,
  `post_limit` INT UNSIGNED NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`blog_id`),
  CONSTRAINT `ck_blog_settings_0` CHECK (posts_per_page BETWEEN 1 AND 100)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Mở rộng: tên miền tùy chỉnh; chỉ phục vụ khi xác minh chủ sở hữu DNS và HTTPS thành công.
CREATE TABLE `blog_domains` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `blog_id` BIGINT UNSIGNED NOT NULL,
  `hostname` VARCHAR(253) NOT NULL,
  `status` ENUM('PENDING','VERIFIED','ACTIVE','FAILED','DISABLED') NOT NULL DEFAULT 'PENDING',
  `verification_token_hash` CHAR(64) NOT NULL,
  `verified_at` DATETIME NULL,
  `activated_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `u_0` (`hostname`),
  UNIQUE KEY `u_1` (`blog_id`,`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Loại mẫu/ngành/chủ đề trong thư viện template như ảnh tham khảo.
CREATE TABLE `template_categories` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(160) NOT NULL,
  `slug` VARCHAR(160) NOT NULL,
  `kind` VARCHAR(20) NOT NULL,
  `parent_id` BIGINT UNSIGNED NULL,
  `is_active` BOOLEAN NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `u_0` (`kind`,`slug`),
  CONSTRAINT `ck_template_categories_0` CHECK (kind IN ('TYPE','INDUSTRY','TOPIC'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Một hàng cho mỗi mẫu. Cấu trúc trang/section/theme/menu/rules gộp vào template_data JSON; draft_data giữ một bản nháp. Không có các bảng chi tiết hoặc lịch sử template.
CREATE TABLE `templates` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(160) NOT NULL,
  `slug` VARCHAR(160) NOT NULL,
  `description` TEXT NULL,
  `status` ENUM('DRAFT','PUBLISHED','HIDDEN','RETIRED') NOT NULL DEFAULT 'DRAFT',
  `preview_media_id` BIGINT UNSIGNED NULL,
  `demo_url` VARCHAR(2048) NULL,
  `created_by` BIGINT UNSIGNED NOT NULL,
  `updated_by` BIGINT UNSIGNED NOT NULL,
  `deleted_at` DATETIME NULL,
  `version_number` INT UNSIGNED NOT NULL DEFAULT 0,
  `template_data` JSON NULL,
  `draft_data` JSON NULL,
  `published_by` BIGINT UNSIGNED NULL,
  `published_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `u_0` (`slug`),
  KEY `i_0` (`status`,`created_at`),
  CONSTRAINT `ck_templates_0` CHECK ((template_data IS NOT NULL OR draft_data IS NOT NULL)),
  CONSTRAINT `ck_templates_1` CHECK (status <> 'PUBLISHED' OR template_data IS NOT NULL),
  CONSTRAINT `ck_templates_2` CHECK ((template_data IS NULL AND version_number = 0) OR (template_data IS NOT NULL AND version_number > 0))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- N:M phân loại template.
CREATE TABLE `template_category_assignments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `template_id` BIGINT UNSIGNED NOT NULL,
  `category_id` BIGINT UNSIGNED NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `u_0` (`template_id`,`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Nội dung và SEO trang tĩnh cùng một hàng; content_json là Tiptap JSON. Layout và menu nằm trong blog_designs. page_key ổn định để binding.
CREATE TABLE `pages` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `blog_id` BIGINT UNSIGNED NOT NULL,
  `title` VARCHAR(160) NOT NULL,
  `slug` VARCHAR(160) NOT NULL,
  `page_key` VARCHAR(80) NOT NULL,
  `content_json` JSON NOT NULL,
  `seo_title` VARCHAR(255) NULL,
  `seo_description` VARCHAR(500) NULL,
  `og_media_id` BIGINT UNSIGNED NULL,
  `canonical_url` VARCHAR(2048) NULL,
  `robots` VARCHAR(80) NOT NULL DEFAULT 'index,follow',
  `draft_data` JSON NULL,
  `status` ENUM('DRAFT','SCHEDULED','PUBLISHED','ARCHIVED') NOT NULL DEFAULT 'DRAFT',
  `scheduled_at` DATETIME NULL,
  `published_at` DATETIME NULL,
  `created_by` BIGINT UNSIGNED NOT NULL,
  `updated_by` BIGINT UNSIGNED NOT NULL,
  `deleted_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `u_0` (`blog_id`,`slug`),
  UNIQUE KEY `u_1` (`blog_id`,`page_key`),
  UNIQUE KEY `u_2` (`blog_id`,`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Một hàng cho mỗi bài: nội dung Tiptap JSON, ảnh cover và SEO. draft_data giữ tối đa một bản chỉnh sửa chờ đăng; không có lịch sử phiên bản bài viết.
CREATE TABLE `posts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `blog_id` BIGINT UNSIGNED NOT NULL,
  `title` VARCHAR(160) NOT NULL,
  `slug` VARCHAR(160) NOT NULL,
  `author_user_id` BIGINT UNSIGNED NOT NULL,
  `excerpt` TEXT NULL,
  `content_json` JSON NOT NULL,
  `cover_media_id` BIGINT UNSIGNED NULL,
  `seo_title` VARCHAR(255) NULL,
  `seo_description` VARCHAR(500) NULL,
  `og_media_id` BIGINT UNSIGNED NULL,
  `canonical_url` VARCHAR(2048) NULL,
  `robots` VARCHAR(80) NOT NULL DEFAULT 'index,follow',
  `draft_data` JSON NULL,
  `status` ENUM('DRAFT','SCHEDULED','PUBLISHED','ARCHIVED') NOT NULL DEFAULT 'DRAFT',
  `scheduled_at` DATETIME NULL,
  `published_at` DATETIME NULL,
  `is_featured` BOOLEAN NOT NULL DEFAULT 0,
  `updated_by` BIGINT UNSIGNED NOT NULL,
  `deleted_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `u_0` (`blog_id`,`slug`),
  UNIQUE KEY `u_1` (`blog_id`,`id`),
  KEY `i_0` (`blog_id`,`status`,`published_at`),
  KEY `i_1` (`status`,`scheduled_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Danh mục bài viết trong từng blog; không dùng lại blog_topics.
CREATE TABLE `categories` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `blog_id` BIGINT UNSIGNED NOT NULL,
  `name` VARCHAR(160) NOT NULL,
  `slug` VARCHAR(160) NOT NULL,
  `description` TEXT NULL,
  `parent_id` BIGINT UNSIGNED NULL,
  `is_active` BOOLEAN NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `u_0` (`blog_id`,`slug`),
  UNIQUE KEY `u_1` (`blog_id`,`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tag riêng trong mỗi blog.
CREATE TABLE `tags` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `blog_id` BIGINT UNSIGNED NOT NULL,
  `name` VARCHAR(160) NOT NULL,
  `slug` VARCHAR(160) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `u_0` (`blog_id`,`slug`),
  UNIQUE KEY `u_1` (`blog_id`,`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- N:M với composite FK bảo vệ phạm vi blog.
CREATE TABLE `post_categories` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `blog_id` BIGINT UNSIGNED NOT NULL,
  `post_id` BIGINT UNSIGNED NOT NULL,
  `category_id` BIGINT UNSIGNED NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `u_0` (`post_id`,`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- N:M với composite FK bảo vệ phạm vi blog.
CREATE TABLE `post_tags` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `blog_id` BIGINT UNSIGNED NOT NULL,
  `post_id` BIGINT UNSIGNED NOT NULL,
  `tag_id` BIGINT UNSIGNED NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `u_0` (`post_id`,`tag_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Một thư viện Cloudinary dùng chung. JSON nội dung lưu media_id và URL; không có bảng liên kết media riêng. Backend kiểm tra quyền và tất cả tham chiếu JSON trước khi xóa ảnh.
CREATE TABLE `media_assets` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `scope` ENUM('PLATFORM','BLOG','USER') NOT NULL,
  `blog_id` BIGINT UNSIGNED NULL,
  `uploaded_by` BIGINT UNSIGNED NOT NULL,
  `cloud_name` VARCHAR(120) NOT NULL,
  `cloudinary_asset_id` VARCHAR(128) NULL,
  `cloudinary_public_id` VARCHAR(255) NULL,
  `cloudinary_version` BIGINT UNSIGNED NULL,
  `resource_type` VARCHAR(20) NOT NULL DEFAULT 'image',
  `delivery_type` VARCHAR(40) NOT NULL DEFAULT 'upload',
  `secure_url` VARCHAR(2048) NULL,
  `original_name` VARCHAR(255) NOT NULL,
  `format` VARCHAR(40) NULL,
  `mime_type` VARCHAR(120) NULL,
  `width` INT UNSIGNED NULL,
  `height` INT UNSIGNED NULL,
  `bytes` BIGINT UNSIGNED NULL,
  `alt_text` VARCHAR(500) NULL,
  `status` ENUM('UPLOADING','READY','FAILED','DELETING','DELETED') NOT NULL DEFAULT 'UPLOADING',
  `deleted_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `u_0` (`cloud_name`,`cloudinary_asset_id`),
  KEY `i_0` (`blog_id`,`status`,`created_at`),
  CONSTRAINT `ck_media_assets_0` CHECK ((scope = 'BLOG' AND blog_id IS NOT NULL) OR (scope IN ('PLATFORM','USER') AND blog_id IS NULL))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Báo cáo blog hoặc bài viết. Không có báo cáo bình luận. Platform Admin xử lý theo quyền; thay đổi ghi vào admin_audit_logs.
CREATE TABLE `content_reports` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `blog_id` BIGINT UNSIGNED NOT NULL,
  `target_type` ENUM('BLOG','POST') NOT NULL,
  `post_id` BIGINT UNSIGNED NULL,
  `reporter_user_id` BIGINT UNSIGNED NULL,
  `reason` TEXT NOT NULL,
  `status` ENUM('OPEN','REVIEWING','RESOLVED','DISMISSED') NOT NULL DEFAULT 'OPEN',
  `assigned_to` BIGINT UNSIGNED NULL,
  `resolved_by` BIGINT UNSIGNED NULL,
  `resolution_note` TEXT NULL,
  `resolved_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `i_0` (`status`,`created_at`),
  CONSTRAINT `ck_content_reports_0` CHECK ((target_type = 'BLOG' AND post_id IS NULL) OR (target_type = 'POST' AND post_id IS NOT NULL))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Thao tác nền tảng và blog. resource_type/resource_id là snapshot log, không phải quan hệ đa hình bảo đảm FK.
CREATE TABLE `admin_audit_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `actor_user_id` BIGINT UNSIGNED NOT NULL,
  `blog_id` BIGINT UNSIGNED NULL,
  `action` VARCHAR(120) NOT NULL,
  `resource_type` VARCHAR(80) NOT NULL,
  `resource_id` VARCHAR(80) NULL,
  `before_data` JSON NULL,
  `after_data` JSON NULL,
  `ip_address` VARCHAR(45) NULL,
  `request_id` VARCHAR(64) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `i_0` (`actor_user_id`,`created_at`),
  KEY `i_1` (`blog_id`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Một bản ghi id=1. config_json chứa branding/SEO/discovery/limits/features/security; backend chỉ trả các khóa public được cho phép. Secrets ở env.
CREATE TABLE `platform_settings` (
  `id` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `config_json` JSON NOT NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `ck_platform_settings_0` CHECK (id = 1),
  CONSTRAINT `ck_platform_settings_1` CHECK (JSON_TYPE(config_json) = 'OBJECT')
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Thông báo cho user; delivery email do queue thực hiện. recipient_user_id là FK trực tiếp nên cần custom notification channel/mapping nếu dùng Laravel DatabaseNotification mặc định.
CREATE TABLE `notifications` (
  `id` CHAR(36) NOT NULL,
  `recipient_user_id` BIGINT UNSIGNED NOT NULL,
  `type` VARCHAR(160) NOT NULL,
  `data` JSON NOT NULL,
  `read_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `i_0` (`recipient_user_id`,`read_at`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 1:1 với blog. published_data/draft_data lưu toàn bộ layout, theme, sections, menu và snapshot template; publish đổi JSON trong một transaction, không tạo hàng revision.
CREATE TABLE `blog_designs` (
  `blog_id` BIGINT UNSIGNED NOT NULL,
  `template_id` BIGINT UNSIGNED NULL,
  `draft_template_id` BIGINT UNSIGNED NULL,
  `published_data` JSON NULL,
  `draft_data` JSON NULL,
  `published_at` DATETIME NULL,
  `updated_by` BIGINT UNSIGNED NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`blog_id`),
  CONSTRAINT `ck_blog_designs_0` CHECK ((published_data IS NOT NULL OR draft_data IS NOT NULL)),
  CONSTRAINT `ck_blog_designs_1` CHECK ((template_id IS NULL AND published_data IS NULL) OR (template_id IS NOT NULL AND published_data IS NOT NULL)),
  CONSTRAINT `ck_blog_designs_2` CHECK ((draft_template_id IS NULL AND draft_data IS NULL) OR (draft_template_id IS NOT NULL AND draft_data IS NOT NULL))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `platform_role_permissions` ADD CONSTRAINT `fk_0_platform_role_permissions` FOREIGN KEY (`role_id`) REFERENCES `platform_roles` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `user_platform_roles` ADD CONSTRAINT `fk_1_user_platform_roles` FOREIGN KEY (`role_id`) REFERENCES `platform_roles` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `platform_role_permissions` ADD CONSTRAINT `fk_2_platform_role_permissions` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `user_platform_roles` ADD CONSTRAINT `fk_3_user_platform_roles` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `user_platform_roles` ADD CONSTRAINT `fk_4_user_platform_roles` FOREIGN KEY (`granted_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `sessions` ADD CONSTRAINT `fk_5_sessions` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT;
ALTER TABLE `account_restrictions` ADD CONSTRAINT `fk_6_account_restrictions` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `account_restrictions` ADD CONSTRAINT `fk_7_account_restrictions` FOREIGN KEY (`issued_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `account_restrictions` ADD CONSTRAINT `fk_8_account_restrictions` FOREIGN KEY (`revoked_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `security_audit_logs` ADD CONSTRAINT `fk_9_security_audit_logs` FOREIGN KEY (`actor_user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `security_audit_logs` ADD CONSTRAINT `fk_10_security_audit_logs` FOREIGN KEY (`subject_user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `blog_topics` ADD CONSTRAINT `fk_11_blog_topics` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `blogs` ADD CONSTRAINT `fk_12_blogs` FOREIGN KEY (`owner_user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `blog_topic_assignments` ADD CONSTRAINT `fk_13_blog_topic_assignments` FOREIGN KEY (`blog_id`) REFERENCES `blogs` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `blog_topic_assignments` ADD CONSTRAINT `fk_14_blog_topic_assignments` FOREIGN KEY (`topic_id`) REFERENCES `blog_topics` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `blog_settings` ADD CONSTRAINT `fk_15_blog_settings` FOREIGN KEY (`blog_id`) REFERENCES `blogs` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `blog_domains` ADD CONSTRAINT `fk_16_blog_domains` FOREIGN KEY (`blog_id`) REFERENCES `blogs` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `template_categories` ADD CONSTRAINT `fk_17_template_categories` FOREIGN KEY (`parent_id`) REFERENCES `template_categories` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `templates` ADD CONSTRAINT `fk_18_templates` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `templates` ADD CONSTRAINT `fk_19_templates` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `template_category_assignments` ADD CONSTRAINT `fk_20_template_category_assignments` FOREIGN KEY (`template_id`) REFERENCES `templates` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `template_category_assignments` ADD CONSTRAINT `fk_21_template_category_assignments` FOREIGN KEY (`category_id`) REFERENCES `template_categories` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `pages` ADD CONSTRAINT `fk_22_pages` FOREIGN KEY (`blog_id`) REFERENCES `blogs` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `pages` ADD CONSTRAINT `fk_23_pages` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `pages` ADD CONSTRAINT `fk_24_pages` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `posts` ADD CONSTRAINT `fk_25_posts` FOREIGN KEY (`blog_id`) REFERENCES `blogs` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `posts` ADD CONSTRAINT `fk_26_posts` FOREIGN KEY (`author_user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `posts` ADD CONSTRAINT `fk_27_posts` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `categories` ADD CONSTRAINT `fk_28_categories` FOREIGN KEY (`blog_id`) REFERENCES `blogs` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `categories` ADD CONSTRAINT `fk_29_categories` FOREIGN KEY (`blog_id`,`parent_id`) REFERENCES `categories` (`blog_id`,`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `tags` ADD CONSTRAINT `fk_30_tags` FOREIGN KEY (`blog_id`) REFERENCES `blogs` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `post_categories` ADD CONSTRAINT `fk_31_post_categories` FOREIGN KEY (`blog_id`,`post_id`) REFERENCES `posts` (`blog_id`,`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `post_categories` ADD CONSTRAINT `fk_32_post_categories` FOREIGN KEY (`blog_id`,`category_id`) REFERENCES `categories` (`blog_id`,`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `post_tags` ADD CONSTRAINT `fk_33_post_tags` FOREIGN KEY (`blog_id`,`post_id`) REFERENCES `posts` (`blog_id`,`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `post_tags` ADD CONSTRAINT `fk_34_post_tags` FOREIGN KEY (`blog_id`,`tag_id`) REFERENCES `tags` (`blog_id`,`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `media_assets` ADD CONSTRAINT `fk_35_media_assets` FOREIGN KEY (`blog_id`) REFERENCES `blogs` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `media_assets` ADD CONSTRAINT `fk_36_media_assets` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `users` ADD CONSTRAINT `fk_37_users` FOREIGN KEY (`avatar_media_id`) REFERENCES `media_assets` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `blog_settings` ADD CONSTRAINT `fk_38_blog_settings` FOREIGN KEY (`logo_media_id`) REFERENCES `media_assets` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `blog_settings` ADD CONSTRAINT `fk_39_blog_settings` FOREIGN KEY (`favicon_media_id`) REFERENCES `media_assets` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `blog_settings` ADD CONSTRAINT `fk_40_blog_settings` FOREIGN KEY (`og_media_id`) REFERENCES `media_assets` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `templates` ADD CONSTRAINT `fk_41_templates` FOREIGN KEY (`preview_media_id`) REFERENCES `media_assets` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `content_reports` ADD CONSTRAINT `fk_42_content_reports` FOREIGN KEY (`blog_id`) REFERENCES `blogs` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `content_reports` ADD CONSTRAINT `fk_43_content_reports` FOREIGN KEY (`blog_id`,`post_id`) REFERENCES `posts` (`blog_id`,`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `content_reports` ADD CONSTRAINT `fk_44_content_reports` FOREIGN KEY (`reporter_user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `content_reports` ADD CONSTRAINT `fk_45_content_reports` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `content_reports` ADD CONSTRAINT `fk_46_content_reports` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `admin_audit_logs` ADD CONSTRAINT `fk_47_admin_audit_logs` FOREIGN KEY (`actor_user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `admin_audit_logs` ADD CONSTRAINT `fk_48_admin_audit_logs` FOREIGN KEY (`blog_id`) REFERENCES `blogs` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `platform_settings` ADD CONSTRAINT `fk_49_platform_settings` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `notifications` ADD CONSTRAINT `fk_50_notifications` FOREIGN KEY (`recipient_user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `posts` ADD CONSTRAINT `fk_51_posts` FOREIGN KEY (`cover_media_id`) REFERENCES `media_assets` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `posts` ADD CONSTRAINT `fk_52_posts` FOREIGN KEY (`og_media_id`) REFERENCES `media_assets` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `templates` ADD CONSTRAINT `fk_53_templates` FOREIGN KEY (`published_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `pages` ADD CONSTRAINT `fk_54_pages` FOREIGN KEY (`og_media_id`) REFERENCES `media_assets` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `blog_designs` ADD CONSTRAINT `fk_55_blog_designs` FOREIGN KEY (`blog_id`) REFERENCES `blogs` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `blog_designs` ADD CONSTRAINT `fk_56_blog_designs` FOREIGN KEY (`template_id`) REFERENCES `templates` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `blog_designs` ADD CONSTRAINT `fk_57_blog_designs` FOREIGN KEY (`draft_template_id`) REFERENCES `templates` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `blog_designs` ADD CONSTRAINT `fk_58_blog_designs` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
