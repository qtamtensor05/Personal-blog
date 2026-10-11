# Yêu cầu thiết kế và triển khai giao diện Blog Platform

## 1. Mục tiêu và phạm vi

Thiết kế nền tảng tạo blog từ mẫu, với bốn khu vực: website chính, Platform Admin, Blog Admin của chủ blog và website blog công khai. Hai ảnh thiết kế đi kèm cuộc trao đổi minh họa trang quản lý mẫu của Platform Admin và trang chọn mẫu có khung đăng nhập của website chính. Tên BlogSpace, số liệu, ảnh nội dung và tên mẫu trong ảnh là dữ liệu minh họa, có thể thay bằng thương hiệu thật.

Tài liệu này bổ sung yêu cầu giao diện cho ERD và kiến trúc đã thống nhất. Khi chữ nhỏ hoặc số liệu trong ảnh khác tài liệu, áp dụng quy tắc nghiệp vụ trong tài liệu và schema hiện có. Không suy ra tính năng mới chỉ từ nội dung trong thumbnail.

Các quyết định bắt buộc:

- Mỗi blog có một chủ sở hữu. Không thiết kế Comment, Blog Invitation, Blog Member, Blog Follow, Blog Moderator hay Post Daily Stat.
- Platform Admin vẫn có quản lý báo cáo vi phạm và kiểm duyệt ở cấp nền tảng. Mục Báo cáo trong menu nghĩa là báo cáo vi phạm, không phải thống kê lượt xem theo ngày.
- Nội dung bài viết và trang dùng JSON; tham chiếu media đặt trong nội dung. Cloudinary là nơi lưu tài nguyên, không tạo bảng riêng cho từng block hay ảnh trong bài.
- Mẫu và cấu hình giao diện dùng JSON theo mô hình template và blog_designs đã chốt. Platform settings dùng cấu hình JSON theo nhóm. Người dùng thao tác bằng biểu mẫu, không phải nhập JSON.
- Có bản nháp, xem trước và xuất bản; không bổ sung bảng lịch sử phiên bản. Xuất bản mẫu mới không tự thay giao diện blog đang chạy.

## 2. Phong cách và design tokens

Phong cách tổng thể: sáng, phẳng, gọn, ưu tiên ảnh xem trước mẫu. Platform Admin mang tính quản trị; website chính thân thiện và có tính biên tập. Dùng cùng hệ thống nút, màu, khoảng cách và trạng thái ở các khu vực.

| Token | Giá trị đề xuất | Sử dụng |
| --- | --- | --- |
| Brand primary | #147D73 | Nút chính, liên kết, trạng thái chọn |
| Brand hover | #0F665E | Hover nút chính |
| Canvas | #F7F8FA | Nền ứng dụng |
| Surface | #FFFFFF | Card, form, menu |
| Primary text | #172B35 | Tiêu đề, nội dung |
| Secondary text | #526575 | Chú thích, mô tả |
| Border | #DCE3E8 | Viền input, card, divider |
| Mint surface | #E8F5F2 | Navigation active, nhãn nhẹ |
| Danger | #B42318 | Lỗi, thao tác nguy hiểm |
| Warning | #8A5300 | Cảnh báo và bản nháp |
| Radius | 8px input, 12px card | Dùng thống nhất |
| Spacing | 4, 8, 12, 16, 24, 32, 48, 64px | Quy tắc khoảng cách |

Dùng font sans hỗ trợ đầy đủ dấu tiếng Việt, ưu tiên font hệ thống hoặc font được host cục bộ. Body 16px website chính; 14–16px quản trị; line-height 1.5. Heading trang 28–32px; hero desktop 48–64px. Serif có thể dùng cho hero và thumbnail mẫu, không dùng cho form quản trị. Không dùng chữ nhạt khó đọc cho thông tin cần thiết. Bóng đổ nhẹ, không lạm dụng gradient, glassmorphism hoặc hiệu ứng 3D.

Ảnh template có tỷ lệ nhất quán 16:10, ưu tiên bản chụp giao diện thật. Thumbnail chỉ để xem nhanh; nút Xem trước mở bản demo tương tác. Không nhúng toàn bộ giao diện sản phẩm bằng một ảnh raster.

## 3. Bố cục responsive và khả năng truy cập

- Website chính: container tối đa 1280px, padding desktop 32px, mobile 16px; gallery ba cột từ 1200px, hai cột từ 768px, một cột dưới 768px.
- Admin: sidebar khoảng 232px desktop, topbar 64px. Sidebar thu thành drawer dưới 1024px; bảng cho phép cuộn trong vùng riêng hoặc chuyển card có nhãn trên mobile.
- Hero và login: desktop hai cột; tablet/mobile xếp dọc. Khi đã đăng nhập, thay login card bằng lối tắt Blog của tôi và Tạo blog. Trên mobile, nút đăng nhập mở trang riêng để tránh hero quá dài.
- Không có tràn ngang toàn trang ở chiều rộng 360px. Editor có thể yêu cầu desktop để sửa bố cục nâng cao; mobile vẫn xem trước, sửa nội dung đơn giản và xem cảnh báo rõ ràng.
- Input có label cố định; placeholder không thay label. Mọi icon button có tên truy cập. Focus hiển thị rõ; thao tác chính dùng bàn phím được; tương phản văn bản thông thường tối thiểu 4.5:1.
- Vùng chạm chính khoảng 44px. Modal giữ focus và trả focus khi đóng; Escape đóng nếu không đang thực hiện thao tác bắt buộc. Lỗi hiển thị tại trường và có tóm tắt nếu form dài.
- Phân biệt trạng thái bằng chữ hoặc icon kèm màu. Ảnh nội dung có alt text; skeleton không gây nhấp nháy. Tôn trọng reduced-motion.

## 4. Bản đồ màn hình

| Khu vực | Màn hình | Ưu tiên |
| --- | --- | --- |
| Website chính | Trang chọn mẫu, chi tiết mẫu, demo mẫu | P0 |
| Website chính | Đăng nhập, đăng ký, xác minh email, quên/reset mật khẩu | P0 |
| Website chính | Blog của tôi, tạo blog từ mẫu | P0 |
| Blog Admin | Tổng quan, bài viết, trang, media, giao diện, cài đặt blog | P0 |
| Platform Admin | Mẫu giao diện, editor mẫu, người dùng, blog, cài đặt | P0 |
| Website blog | Trang chủ, bài viết, trang tĩnh, danh mục/thẻ, tìm kiếm | P0 |
| Platform Admin | Chủ đề, báo cáo vi phạm, nhật ký, thông báo | P1 |
| Website chính | Khám phá blog, hướng dẫn sử dụng | P1 |

P0 là luồng cần hoàn chỉnh trước khi phát hành; P1 triển khai tiếp sau khi luồng tạo–viết–xuất bản–đọc hoạt động. Các chức năng đã bị loại không thuộc P1 hay backlog mặc định.

## 5. Website chính: chọn mẫu và xác thực

Header gồm logo, Khám phá, Mẫu blog, Hướng dẫn. Guest thấy Đăng nhập và Bắt đầu; user thấy Blog của tôi và menu tài khoản. Trang chọn mẫu có hero ngắn, gallery và bộ lọc. Trong ảnh desktop, login card ở bên phải hero là một phương án bố cục; chức năng xác thực phải có URL riêng để mở trực tiếp và dùng trên mobile.

Gallery:

- Card gồm thumbnail, tên, chủ đề, mô tả ngắn, Xem trước và Dùng mẫu. Tối thiểu ba demo hoàn chỉnh: Nhật ký, Hành trình, Tạp chí. Ba mẫu còn lại trong ảnh là hướng mở rộng, chỉ hiển thị khi đã có demo thật.
- Tìm kiếm theo tên và mô tả; lọc theo chủ đề. Giữ từ khóa, bộ lọc, phân trang trong URL để quay lại và chia sẻ.
- Chỉ hiển thị mẫu đã xuất bản và được phép chọn. Nhãn Phổ biến chỉ hiện khi có quy tắc/dữ liệu thực; không gắn ngẫu nhiên theo mockup.
- Không tìm thấy: nêu từ khóa và nút Xóa bộ lọc. Lỗi tải có Thử lại. Loading có skeleton cùng kích thước card để hạn chế dịch chuyển.
- Chi tiết mẫu có ảnh lớn, mô tả, bố cục hỗ trợ, khả năng tùy chỉnh và CTA Dùng mẫu. Demo có thanh trở lại thư viện, chọn viewport và Dùng mẫu; không chứa hành động gửi form thật hoặc sửa dữ liệu live.

Luồng Dùng mẫu: guest chọn mẫu → đăng nhập/đăng ký → giữ lại mẫu đã chọn bằng tham chiếu được kiểm tra phía server → xác minh email nếu cần → nhập tên blog và slug → kiểm tra quota và quyền → tạo blog, thiết lập giao diện từ snapshot mẫu → mở Blog Admin. Không mất lựa chọn khi chuyển qua bước xác thực. Nếu mẫu bị gỡ hoặc quota hết, giải thích và cho chọn lại/đi tới danh sách blog; không tạo blog rỗng một phần.

Form đăng nhập có email, mật khẩu, hiện/ẩn mật khẩu, quên mật khẩu và đăng ký. Không tự thêm đăng nhập xã hội. Đăng ký có tên, email, mật khẩu và xác nhận. Các trang xác minh/reset thể hiện trạng thái hết hạn, đã dùng, gửi lại và thời gian chờ. Không hiển thị thông tin giúp xác định tài khoản khác có tồn tại hay không qua lỗi đăng nhập/reset.

## 6. Platform Admin

Menu: Tổng quan, Người dùng, Blog, Mẫu giao diện, Chủ đề, Media, Báo cáo vi phạm, Cài đặt, Nhật ký. Tài khoản admin và đăng xuất ở cuối sidebar. Quyền quyết định menu và thao tác; API vẫn kiểm tra quyền, không chỉ ẩn nút. Admin có bước 2FA theo kiến trúc hiện có.

Màn hình Mẫu giao diện theo ảnh: heading, Tạo mẫu, các số đếm thực, search, trạng thái, chủ đề, gallery và phân trang. Thẻ thể hiện trạng thái, chủ đề, Xem trước, Chỉnh sửa; menu phụ chỉ chứa thao tác thực sự được hỗ trợ. Số Blog đang sử dụng phải tính từ tham chiếu mẫu đã xuất bản của blog, không từ mẫu bản nháp đang chọn. Dữ liệu trong ảnh không được hardcode vào sản phẩm.

Form mẫu gồm tên, slug, mô tả, ảnh xem trước, chủ đề và thiết lập giao diện. Mở editor để sửa nội dung mẫu. Action Lưu nháp, Xem trước, Xuất bản hiển thị rõ. Khi xuất bản, giải thích rằng blog đã tạo vẫn giữ giao diện hiện tại. Không có timeline phiên bản hay nút phục hồi phiên bản lịch sử. Thao tác ngừng cho chọn mẫu phải theo status thật trong schema; không tự bổ sung enum chỉ để khớp thiết kế.

Người dùng: bảng search/filter, trạng thái tài khoản, chi tiết người dùng, blog thuộc sở hữu, các hành động đúng quyền hiện có. Blog: tên, slug, owner, trạng thái, mẫu đang dùng, liên kết xem công khai; quản lý trạng thái cấp nền tảng phải có lý do và xác nhận. Không có quản lý thành viên blog.

Cài đặt nền tảng: các tab Thương hiệu, SEO, Khám phá, Giới hạn, Tính năng, Bảo mật. Field có đơn vị và giá trị hợp lệ. Lưu qua cấu hình JSON hiện có, kiểm tra schema phía server và chỉ cập nhật nhóm đang sửa. Không đưa API secret, khóa Cloudinary, SMTP password hoặc credential vào form công khai hay projection settings gửi tới browser.

Báo cáo vi phạm: danh sách đối tượng, lý do, trạng thái, chi tiết nội dung và thao tác xử lý theo quyền. Nhật ký là read-only, có actor, hành động, đối tượng, thời gian và bộ lọc. Không dùng báo cáo vi phạm làm nơi hiển thị lượt xem hằng ngày.

## 7. Blog Admin của Owner

Có bộ chọn blog ở topbar nếu user sở hữu nhiều blog. Luôn hiển thị blog đang thao tác; điều hướng và query phải giữ đúng blog context. Menu: Tổng quan, Bài viết, Trang, Danh mục, Thẻ, Media, Giao diện, Menu điều hướng, Cài đặt. Danh mục/thẻ chỉ dùng bảng và nghiệp vụ có trong ERD hiện tại.

Tổng quan hiển thị tình trạng blog, số bài/trang, nội dung gần đây và CTA Viết bài/Xem blog. Không hiển thị biểu đồ lưu lượng hoặc daily stats. Blog chưa xuất bản có hướng dẫn tạo bài đầu tiên, sửa giao diện và xem trước.

Danh sách bài viết: search, trạng thái, danh mục, thời gian; mỗi dòng có tiêu đề, trạng thái, ngày xuất bản/lên lịch và Sửa. Form bài viết: tiêu đề, slug, mô tả, Tiptap editor, ảnh bìa, danh mục/thẻ, SEO, trạng thái và lịch xuất bản. Thanh thao tác sticky có Lưu nháp, Xem trước, Xuất bản hoặc Lên lịch tùy trạng thái được backend hỗ trợ. Trang tĩnh dùng cùng editor nhưng không tự thêm danh mục/thẻ của bài viết.

Phân biệt bài đang live với thay đổi nháp. Sửa một bài đã xuất bản không tự làm nội dung nháp xuất hiện công khai. Lỗi lưu giữ nội dung trên màn hình và cho thử lại. Hiển thị Đang lưu/Đã lưu/Chưa lưu khi có cơ chế tương ứng. Chỉ tự động lưu nháp nếu đã triển khai xử lý cạnh tranh ghi; không giả lập trạng thái autosave.

Cài đặt blog: tên, slug, mô tả, logo, favicon, SEO và các cấu hình đã có trong schema. Slug thay đổi phải có thông báo tác động tới URL theo chính sách routing thực tế. Không thêm custom domain hoặc gói thanh toán vào MVP khi chưa được chốt.

## 8. Editor mẫu và giao diện blog

Editor desktop gồm thanh trên, panel trái, canvas giữa, panel phải. Thanh trên: tên mẫu/blog, trạng thái lưu, chọn desktop/tablet/mobile, Xem trước, Lưu nháp, Xuất bản. Panel trái: danh sách section và thêm section; canvas: vùng dựng giao diện; panel phải: thuộc tính section đang chọn. Dùng GrapesJS trong giới hạn component/section đã cho phép, không cung cấp nơi chạy JavaScript tùy ý.

Người dùng chỉnh màu, font, logo, khoảng cách, hero, bố cục danh sách bài, header/footer và menu qua control trực quan. Dữ liệu JSON nằm sau hệ thống form/editor. Tham chiếu bài viết, danh mục, media dùng picker có scope; không nhập ID thủ công. Nội dung bài viết động lấy từ binding, không nhân bản thành dữ liệu tĩnh trong mẫu.

Mẫu quy định section được thêm/xóa/sắp xếp và trường có thể sửa. Owner chỉ thấy tùy chọn mẫu hỗ trợ. Khi đổi mẫu của blog: xem trước mẫu mới, cảnh báo thay đổi bố cục và nội dung giao diện tùy chỉnh, lưu vào nháp, chỉ áp dụng live khi xuất bản. Nội dung bài/trang và media vẫn được tham chiếu theo dữ liệu blog, không bị xóa bởi đổi mẫu.

Undo/redo trong phiên editor có thể có; không quảng bá đó là lịch sử phiên bản đã lưu. Nếu nhiều tab cùng sửa, khi backend phát hiện dữ liệu mới hơn phải yêu cầu tải bản mới hoặc giải quyết xung đột, không ghi đè âm thầm. Preview nháp chỉ mở cho người có quyền hoặc qua URL được bảo vệ và có thời hạn; không index preview.

## 9. Media và Cloudinary

Media picker dùng chung: grid tài nguyên, tìm theo tên, upload, tiến độ, retry, xem chi tiết, alt text và chọn ảnh. Hiển thị kích thước, dung lượng và loại file theo metadata hiện có. Giới hạn upload lấy từ cấu hình hợp lệ phía server. Upload thành công mới chèn tham chiếu vào nội dung; thất bại không để block media hỏng.

Platform Admin truy cập scope nền tảng theo quyền; Owner chỉ truy cập tài nguyên được phép của blog/user. Không lộ asset private qua URL hoặc search. Cloudinary provider là chi tiết hạ tầng, người dùng thấy Thư viện media. Lưu một media_assets theo schema; content_json và design JSON tham chiếu asset_id cùng thông tin hiển thị cần thiết. Không tạo bảng riêng cho ảnh bìa, ảnh trong block hoặc file của từng template.

Xóa tài nguyên cần kiểm tra tham chiếu; nếu đang dùng, chặn hoặc có quy tắc xử lý rõ ở backend. UI không hứa xóa an toàn nếu hệ thống chưa kiểm tra được các tham chiếu trong JSON.

## 10. Website blog công khai

Renderer dựa trên published_data của blog_designs và nội dung đã xuất bản. Có trang chủ, chi tiết bài, trang tĩnh, danh mục/thẻ, tìm kiếm và 404 trong phong cách mẫu. Header/footer đồng bộ; nội dung có chiều rộng đọc hợp lý, typography rõ và ảnh responsive. Bài viết gồm tiêu đề, ngày, tác giả nếu được phép hiển thị, ảnh bìa, nội dung và bài liên quan theo truy vấn thật.

Không có bình luận, follow hoặc lời mời thành viên. Contact form trong thumbnail chỉ là nội dung minh họa: không đưa vào sản phẩm nếu chưa có nghiệp vụ nhận/gửi liên hệ. Dùng menu hợp lệ và URL có kiểm tra; không render script từ nội dung JSON. Draft và bài lên lịch chưa đến thời điểm xuất bản không xuất hiện ở danh sách, tìm kiếm hay URL public. Blog bị vô hiệu hóa có trang trạng thái phù hợp, không lộ thông tin quản trị.

## 11. Bộ component và trạng thái dùng chung

Các component cần xây dựng: AppShell, PublicHeader, PageHeading, Button, Input, Select, Textarea, Checkbox, FormError, StatusBadge, SearchFilterBar, TemplateCard, BlogCard, DataTable, Pagination, Tabs, Modal, ConfirmDialog, Toast, EmptyState, ErrorState, Skeleton, MediaPicker, EditorToolbar, PreviewViewport.

Mỗi màn hình cần thiết kế và triển khai trạng thái loading, empty, success, validation error, lỗi mạng/server, không có quyền và thao tác đang gửi khi có liên quan. Disable nút submit trong lúc đang gửi nhưng không xóa dữ liệu. Toast chỉ là phản hồi bổ sung, không thay lỗi form. Thao tác xóa/ngừng xuất bản phải gọi đúng đối tượng bằng tên và nêu hệ quả. Chặn điều hướng khi thay đổi chưa lưu; retry không tạo bản ghi trùng.

## 12. Gắn với kiến trúc và chia module

Giữ stack và Modular Monolith đã chốt trong tài liệu kiến trúc; đọc repository trước khi chọn phiên bản, không tự nâng dependency để làm giao diện. Blade/Livewire/Alpine/Tailwind cho website chính và UI động; hai panel Filament riêng cho Platform Admin và Blog Admin. GrapesJS và Tiptap là adapter editor, không giữ toàn bộ nghiệp vụ trong component frontend.

| Module | Trách nhiệm giao diện |
| --- | --- |
| Identity / AccessControl | Xác thực, profile, quyền và 2FA admin |
| Blogs | Blog của tôi, tạo blog, tenant selector, cài đặt blog |
| Templates | Gallery, demo, quản lý mẫu |
| Design | Editor, chọn mẫu cho blog, preview/publish giao diện |
| Content | Bài viết, trang, danh mục, thẻ, SEO và lên lịch |
| Media | Picker, upload và metadata |
| Platform | Form settings theo nhóm |
| Moderation / Audit | Báo cáo vi phạm và nhật ký |
| Notifications | Thông báo thực từ nghiệp vụ hiện có |
| PublicSite | Render blog và trang khám phá |

Áp dụng SOLID: component hiển thị không xử lý SQL; Action xử lý mutation; Query xử lý đọc; DTO/schema kiểm tra payload; Policy kiểm tra quyền; tích hợp Cloudinary/editor qua adapter hoặc contract phù hợp. Không tạo interface cho mọi class khi chưa có mục đích thay thế. Không gọi trực tiếp hạ tầng module khác từ view. Query Builder theo kiến trúc hiện có; Eloquent mỏng cho auth/Filament nếu cần.

## 13. Prompt triển khai giao diện dành cho Codex

Sao chép đoạn dưới cùng tài liệu này, ERD, tài liệu kiến trúc và hai ảnh tham chiếu vào phiên Codex có repository:

> Bạn là kỹ sư triển khai giao diện cho Blog Platform. Đọc AGENTS.md, repository, ERD và tài liệu kiến trúc trước khi sửa. Dùng tài liệu DESIGN_REQUIREMENTS_BLOG_PLATFORM.md làm đặc tả giao diện; hai ảnh làm tham chiếu về bố cục/màu, không thay cho quy tắc nghiệp vụ. Giữ stack và schema hiện tại. Nếu repo trống, dựng nền theo kiến trúc đã chốt; nếu đã có code, nối vào cấu trúc hiện có. Ghi rõ giả định thiếu dữ liệu và tiếp tục với lựa chọn hợp lý, chỉ hỏi khi có mâu thuẫn làm thay đổi nghiệp vụ hoặc gây mất dữ liệu.
>
> Triển khai theo từng lát cắt hoạt động hoàn chỉnh: (1) design tokens, component chung và shell hai panel; (2) gallery, preview mẫu, auth và tạo blog giữ lựa chọn mẫu; (3) quản lý mẫu và editor mẫu; (4) Blog Admin bài/trang/media; (5) editor giao diện blog và published renderer; (6) settings, moderation/audit và các màn hình P1. Mỗi lát cắt phải có luồng dữ liệu thật, kiểm tra quyền và trạng thái lỗi, không để nút giả hoặc số liệu hardcode. Seed demo chỉ trong môi trường phát triển, không sửa dữ liệu production.
>
> Bỏ hoàn toàn các màn hình Comment, Blog Invitation/Member/Follow/Moderator và daily post stats. Một blog có một owner. Nội dung, template/design và settings lưu JSON theo schema; UI dùng form và editor trực quan. Giữ draft và live riêng; cập nhật mẫu không đổi blog live tự động. Không tạo hệ thống lịch sử phiên bản, social login, custom domain, billing hoặc bảng nội dung/media phân mảnh khi chưa được yêu cầu.
>
> Chia module theo bảng trách nhiệm, tách view khỏi Action/Query/Policy/DTO, đảm bảo tenant isolation phía server. Dùng hình/thumbnail thật hoặc demo tự tạo có nguồn hợp lệ; không dùng toàn bộ ảnh mockup làm giao diện. Giao diện tiếng Việt, responsive 360/768/1024/1440px, bàn phím và contrast đạt yêu cầu. Kiểm tra các luồng rủi ro bằng test phù hợp, chạy build và check của repo; dùng browser kiểm tra giao diện nếu môi trường có browser. Không tuyên bố kiểm tra nào đã chạy khi không có bằng chứng.
>
> Làm việc tới khi phạm vi triển khai đã nhận hoàn chỉnh. Báo cáo file thay đổi, luồng hoạt động, kiểm tra đã chạy và phần bị chặn thực sự. Cập nhật README cách chạy local, seed demo, worker/scheduler cần thiết. Không deploy production hoặc thực hiện migration phá hủy dữ liệu chỉ vì hoàn thiện giao diện.

## 14. Điều kiện nghiệm thu

1. Website chính và Platform Admin có cùng design system, gần hai ảnh tham chiếu ở desktop và không tràn ở mobile.
2. Guest xem/lọc/preview mẫu được; Dùng mẫu chuyển qua auth rồi quay về đúng mẫu; chỉ tạo blog khi quyền, email và quota hợp lệ.
3. Admin quản lý mẫu bằng dữ liệu thật; số đếm và status phản ánh backend. Owner không mở được dữ liệu hoặc media của blog khác kể cả khi sửa ID/request.
4. Owner viết bài, chèn media, lưu nháp, preview và xuất bản được. Draft không rò rỉ ra public; lịch xuất bản hoạt động qua worker/scheduler đã cấu hình.
5. Editor mẫu/blog có các control trực quan; đổi mẫu đi qua nháp; publish cập nhật snapshot đúng blog; thay đổi mẫu nguồn không tự đổi blog live.
6. Settings dùng form theo nhóm và JSON hiện có, không làm mất nhóm khác; public projection không có secrets.
7. Có empty/error/loading/permission states; lưu lỗi không mất dữ liệu; double-click hoặc retry không tạo blog/bài trùng.
8. Có kiểm tra tenant isolation, draft visibility, publish và auth-return-flow; chạy build/check phù hợp. Kiểm tra thủ công ở 360, 768, 1024 và 1440px khi có công cụ.
9. Không có các tính năng đã loại; không bổ sung bảng chỉ để phù hợp ảnh; không có link hay nút không hoạt động trong phạm vi bàn giao.

## 15. Bàn giao cho các đợt tiếp theo

Đợt 1 hoàn thiện website chính và Platform Admin quản lý mẫu theo hai ảnh. Đợt 2 làm Blog Admin, trình viết bài và media. Đợt 3 làm editor giao diện và website blog theo mẫu. Đợt 4 hoàn thiện cài đặt, khám phá, kiểm duyệt và nhật ký. Dù chia đợt, đặt nền auth/tenant/policy và schema JSON từ đầu để các phần sau dùng lại.

Mỗi đợt bàn giao gồm UI hoạt động, seed/demo cần thiết, danh sách route, quyền truy cập, trạng thái đã hỗ trợ và kết quả kiểm tra. Hai ảnh hiện tại là thiết kế tham chiếu, không phải website đã được triển khai.
