# Triển khai VPS

Runbook dành cho người vận hành, không phải hướng dẫn cài local. Chạy tại website/; cần reverse proxy HTTPS, secrets, backup/restore đã kiểm chứng. Chưa có nghiệm thu deploy production trong phiên này.

1. Cấp secrets qua `.env`, giữ nguyên `APP_KEY` giữa các lần deploy; đặt `APP_ENV=production`, `APP_DEBUG=false`, cookie secure và URL HTTPS.
2. Build image immutable: `docker compose -f docker-compose.yml -f docker-compose.prod.yml build`.
3. Backup MySQL và kiểm tra file backup có thể đọc trước migration.
4. Chạy đúng một runner, luôn dùng overlay production: `docker compose -f docker-compose.yml -f docker-compose.prod.yml run --rm app php artisan migrate --force`.
5. Cache config/routes/views, rollout app/nginx/worker/scheduler rồi `php artisan queue:restart`.
6. Smoke-test `/up`, trang chủ, một blog public, login và Redis queue. Không gọi Cloudinary trong readiness.

Mọi lệnh up/exec/run trên VPS phải dùng cả hai file Compose như bước 4. Không dùng DemoSeeder, migrate:fresh hoặc down -v. Cache config phải được tạo sau khi nhận environment production, không sao chép config cache từ local. Giữ cùng APP_KEY trong .env/secrets qua các lần recreate; tạo key mới làm mất hiệu lực session và dữ liệu mã hóa.

MySQL và Redis chỉ ở private network; reverse proxy ngoài compose chịu trách nhiệm TLS. Custom domain mặc định tắt (`CUSTOM_DOMAINS_ENABLED=false`) cho đến khi có DNS ownership + HTTPS automation. Backup hằng ngày, mục tiêu RPO 24 giờ/RTO 4 giờ; diễn tập restore theo quý. Theo dõi failed jobs, scheduler heartbeat, dung lượng DB/Redis và log rotation. Rollback image chỉ dùng khi schema tương thích; thay đổi phá hủy phải restore backup có kiểm chứng.
