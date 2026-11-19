# AI hỗ trợ điền bài viết từ ảnh

Trong trình soạn thảo bài viết, bảng **AI hỗ trợ điền bài viết** nằm trước các ô nhập nội dung. Chọn ảnh trực tiếp từ thiết bị; không cần có ảnh sẵn trong bài.

1. Chọn ảnh JPG, PNG hoặc WEBP (tối đa 5 MB), ngôn ngữ Việt/Anh và nhập yêu cầu viết nếu cần.
2. Đánh dấu các mục cần AI điền: tiêu đề, nội dung, đường dẫn, tóm tắt, tiêu đề SEO, mô tả SEO, mô tả ảnh. Mặc định chọn những mục còn trống. Muốn viết lại mục đã có nội dung thì đánh dấu mục đó.
3. Bấm **AI điền các mục đã chọn**. Ảnh được tải lên hệ thống; Gemini nhận ảnh, tiêu đề, văn bản gần con trỏ và yêu cầu viết. Kết quả được điền trực tiếp vào các ô đã chọn, không qua một ô gợi ý riêng.
4. Chỉnh sửa ngay trên các ô của bài viết rồi xuất bản khi đã kiểm tra xong.

Nếu chọn mục nội dung, AI thay nội dung hiện tại bằng một tài liệu có ảnh tải lên, tiêu đề phụ, đoạn văn và danh sách. Tài liệu sử dụng CSS hiện có của website; không sinh/chạy mã HTML hoặc CSS từ AI. Các đoạn văn là node văn bản nên chuỗi trông như HTML cũng không trở thành mã thực thi.

Những mục không đánh dấu được giữ nguyên. Mục người dùng sửa trong lúc AI xử lý cũng được giữ nguyên và được liệt kê trong thông báo kết quả. AI không tự xuất bản bài viết. Trình soạn thảo tự lưu tiêu đề, tóm tắt, nội dung, đường dẫn và SEO vào bản nháp. Đường dẫn/SEO lưu trong JSON `draft_data`, không làm thay đổi phiên bản public trước khi người dùng xuất bản, không cần thay đổi schema cơ sở dữ liệu. Bản nháp cũ vẫn đọc được.

Lưu ý: đường dẫn AI đề xuất có thể trùng với bài viết khác; quy tắc kiểm tra đường dẫn hiện có vẫn được áp dụng khi xuất bản. Tệp ảnh đã tải lên vẫn nằm trong thư viện media nếu người dùng chỉ yêu cầu điền SEO hoặc yêu cầu AI thất bại.

## Cấu hình

Thiết lập trên máy chủ trong `website/.env`:

```dotenv
GEMINI_API_KEY=your_server_side_key
GEMINI_MODEL=gemini-3.5-flash-lite
GEMINI_FALLBACK_MODEL=gemini-3.1-flash-lite
```

Sau khi thay đổi cấu hình, chạy `php artisan config:clear` (hoặc tạo lại config cache theo quy trình triển khai hiện có). Không đặt khóa trong biến `VITE_*`, JavaScript, Blade hoặc Git. Khi chưa có khóa, giao diện vẫn cho tải ảnh và sửa bài bình thường, nhưng nút tạo gợi ý bị vô hiệu hóa kèm thông báo cấu hình.

Model có thể đổi bằng `GEMINI_MODEL`; tài khoản Google phải có quyền sử dụng model đó. Tích hợp dùng REST [`generateContent`](https://ai.google.dev/api/generate-content) với dữ liệu ảnh inline theo [hướng dẫn image understanding](https://ai.google.dev/gemini-api/docs/image-understanding), không cần ảnh public khi lưu trữ cục bộ. Xem [thông tin model](https://ai.google.dev/gemini-api/docs/models/gemini-3.5-flash-lite).

## Giới hạn và xử lý lỗi

- Chỉ chủ sở hữu blog có thể yêu cầu viết cho bài viết của blog đó; chỉ nhận mã ảnh có sẵn trong blog, không nhận URL ảnh tùy ý.
- Ảnh JPG, PNG hoặc WEBP, tối đa 5 MB. Ảnh GIF vẫn có thể tải lên qua luồng cũ nhưng không dùng cho trợ lý AI.
- Hỗ trợ `MEDIA_DRIVER=local` và Cloudinary. Ảnh local được đọc từ disk cấu hình; ảnh Cloudinary được tải từ `https://res.cloudinary.com` với redirect bị tắt và giới hạn dung lượng.
- Tối đa 5 yêu cầu/phút cho mỗi người dùng. Có timeout cho tải ảnh (15 giây) và Gemini (45 giây). Endpoint AI đặt ngân sách PHP 90 giây riêng cho request rồi khôi phục giới hạn trước đó; các endpoint khác giữ nguyên giới hạn. Trình duyệt chờ tối đa 70 giây cho upload và 75 giây cho tạo nội dung, tính riêng từng bước. Chỉ tự thử lại tối đa một lần khi Gemini trả HTTP 502/503/504, sau khoảng một giây. Lần thử lại dùng model trong GEMINI_FALLBACK_MODEL; để biến này rỗng nếu chỉ muốn thử lại model chính. Cả hai lần dùng chung ngân sách chờ 45 giây. Không tự thử lại với lỗi khóa API, cấu hình hoặc hạn mức.
- Phản hồi AI có cấu trúc JSON và được kiểm tra độ dài, định dạng đường dẫn, loại khối nội dung ở máy chủ. Chỉ tạo tài liệu có tiêu đề phụ, đoạn văn và danh sách; không nhận HTML/CSS từ nhà cung cấp. Nội dung có thể sai; người viết kiểm tra trước khi sử dụng.
- Lỗi cấu hình, lỗi đọc ảnh, timeout, giới hạn và phản hồi bị chặn/không đầy đủ có thông báo Việt–Anh. Phân biệt lỗi khóa API, quyền truy cập, model, cấu hình yêu cầu, hạn mức, chứng chỉ, kết nối và dịch vụ tạm ngưng. Chỉ ghi mã HTTP/cURL và tên model trong log; không ghi khóa, nội dung yêu cầu hoặc nguyên thông báo nhà cung cấp. Không trả nguyên lỗi nhà cung cấp hoặc khóa API về trình duyệt.

## Kiểm tra

```shell
php vendor/bin/pest tests/Feature/ImageWritingTest.php tests/Feature/InterfaceLocalizationTest.php tests/Unit
node --test tests/Js/image-writing-document.test.js tests/Js/image-writing-fields.test.js tests/Js/image-writing-image.test.js
npm run lint
npm run build
```

Kiểm thử máy chủ dùng HTTP giả lập, không gọi Gemini thật. Cần cấu hình khóa và thực hiện thử một ảnh thực tế để xác nhận dịch vụ bên ngoài trong môi trường triển khai.

## Tối ưu thời gian và lỗi dịch vụ

- Chỉ yêu cầu/kiểm tra/trả về các mục đã chọn. Khi chỉ điền SEO hoặc tiêu đề, Gemini không phải viết cả bài. Schema JSON chỉ chứa các mục cần sinh.
- Giới hạn đầu ra là 768 token cho các trường thông tin, 4.000 token khi có nội dung. Mặc định nội dung khoảng 180–300 từ; có thể mô tả độ dài mong muốn trong yêu cầu viết.
- Với ảnh trên 256 KB, trình duyệt tạo một bản JPEG dùng riêng cho AI, cạnh dài tối đa 1.024 px, tối đa 1 MB. Ảnh gốc vẫn được tải lên và dùng trong bài; bản thu nhỏ không ghi đè ảnh gốc. Nếu trình duyệt không nén được, luồng cũ vẫn hoạt động.
- Bản ảnh phân tích gửi trực tiếp trong request AI, không phải tải lại ảnh lớn từ Cloudinary. Không lưu bản ảnh phân tích vào thư viện.
- Model dự phòng mặc định là gemini-3.1-flash-lite, chỉ dùng cho lần thử lại sau HTTP 502/503/504. Không dùng model dự phòng để vượt hạn mức 429 hoặc né lỗi khóa/quyền truy cập.
- Log thành công ghi tên model, thời gian xử lý, số byte ảnh gửi và số mục được tạo; không ghi nội dung ảnh, bài viết hoặc khóa.
- Các thay đổi giảm lượng dữ liệu và công việc mỗi yêu cầu; không bảo đảm loại bỏ lỗi quá tải phía Google hoặc bảo đảm thời gian cố định.
