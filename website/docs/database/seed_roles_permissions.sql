-- Fresh schema only; not a migration for an existing production database.
-- No default administrator or password is created.
START TRANSACTION;
INSERT INTO platform_roles (code,name) VALUES ('SUPER_ADMIN','Super Admin'),('PLATFORM_ADMIN','Platform Admin');
INSERT INTO permissions (code,scope,description) VALUES
('platform.dashboard.view','PLATFORM','Xem thống kê nền tảng'),
('platform.users.manage','PLATFORM','Quản lý User thường'),
('platform.users.restrict','PLATFORM','Khóa/mở khóa User thường'),
('platform.templates.manage','PLATFORM','Tạo/sửa template và GrapesJS'),
('platform.templates.publish','PLATFORM','Xuất bản/ẩn template và phiên bản'),
('platform.blogs.moderate','PLATFORM','Duyệt/ẩn/đình chỉ blog'),
('platform.content.moderate','PLATFORM','Xử lý báo cáo và nội dung'),
('platform.topics.manage','PLATFORM','Quản lý chủ đề website chính'),
('platform.audit.view','PLATFORM','Xem nhật ký nền tảng'),
('platform.roles.manage','PLATFORM','Quản lý quyền nền tảng'),
('platform.admins.manage','PLATFORM','Cấp quyền và quản lý Admin'),
('platform.security.manage','PLATFORM','Cấu hình bảo mật'),
('platform.settings.manage','PLATFORM','Cấu hình nền tảng và giới hạn'),
('platform.users.delete','PLATFORM','Quy trình xóa/anonymize tài khoản'),
('platform.media.manage','PLATFORM','Quản lý media nền tảng');
INSERT INTO platform_role_permissions (role_id,permission_id) SELECT r.id,p.id FROM platform_roles r JOIN permissions p ON p.scope='PLATFORM' WHERE r.code='SUPER_ADMIN';
INSERT INTO platform_role_permissions (role_id,permission_id) SELECT r.id,p.id FROM platform_roles r JOIN permissions p ON p.scope='PLATFORM' WHERE r.code='PLATFORM_ADMIN' AND p.code NOT IN ('platform.roles.manage','platform.admins.manage','platform.security.manage','platform.settings.manage','platform.users.delete');
INSERT INTO platform_settings (id,config_json,updated_by) VALUES (1,'{"schemaVersion":1,"branding":{"name":"Blog Platform","logoMediaId":null},"seo":{"title":"Blog Platform","description":"Khám phá và tạo blog cá nhân"},"discovery":{"enabled":true},"limits":{"maxBlogsPerUser":3,"defaultStorageQuotaBytes":104857600,"maxImageUploadBytes":10485760},"features":{"registrationEnabled":true,"customDomainsEnabled":false},"security":{"requireAdmin2fa":true,"requireVerifiedEmailForBlog":true}}',NULL);
COMMIT;
