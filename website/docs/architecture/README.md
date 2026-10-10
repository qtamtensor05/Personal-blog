# Kiến trúc

Ứng dụng là modular monolith Laravel: một codebase, một MySQL nghiệp vụ và các tiến trình web, worker, scheduler dùng cùng release image.

```text
HTTP / Filament / Livewire
  -> validation + policy
  -> action / query + DTO
  -> Query Builder transaction
  -> MySQL / Redis / Cloudinary / mail
```

Quyền ghi bảng thuộc các module Identity, AccessControl, Blogs, Templates, Design, Content, Media, Platform, Moderation, Audit và Notifications. PublicSite chỉ đọc qua query đã lọc public. Owner được suy ra duy nhất từ `blogs.owner_user_id`; không có membership, comment hoặc follower.

## Quyết định kiến trúc

- ADR-001: Query Builder cho use case nghiệp vụ và Eloquent mỏng cho auth/Filament.
- ADR-002: JSON có `schemaVersion` là source of truth; public và draft là hai snapshot, không có revision table.
- ADR-003: tenant lấy từ route/domain đã xác minh, rồi mọi query tiếp tục ràng buộc `blog_id`.
- ADR-004: publish khóa hàng blog + entity trong transaction, kiểm tra fingerprint, copy draft sang public, ghi audit cùng transaction và invalidate cache sau commit.
- ADR-005: thao tác provider media nằm ngoài transaction dài; reference collector và thứ tự lock media tăng dần bảo vệ publish/delete race.

`CreateBlogAction`, `PublishPostAction`, `PublishDesignAction` minh họa SRP/DIP. `SafeSectionRegistry` là allowlist mở rộng cho renderer, từ chối component lạ và chuỗi thực thi. `AuditRecorder` là contract xuyên module.
