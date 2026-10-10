# Triển khai VPS

1. Cấp secrets qua `.env`, giữ nguyên `APP_KEY` giữa các lần deploy; đặt `APP_ENV=production`, `APP_DEBUG=false`, cookie secure và URL HTTPS.
2. Build image immutable: `docker compose -f docker-compose.yml -f docker-compose.prod.yml build`.
3. Backup MySQL và kiểm tra file backup có thể đọc trước migration.
4. Chạy đúng một runner: `docker compose run --rm app php artisan migrate --force`.
5. Cache config/routes/views, rollout app/nginx/worker/scheduler rồi `php artisan queue:restart`.
6. Smoke-test `/up`, trang chủ, một blog public, login và Redis queue. Không gọi Cloudinary trong readiness.

MySQL và Redis chỉ ở private network; reverse proxy ngoài compose chịu trách nhiệm TLS. Custom domain mặc định tắt (`CUSTOM_DOMAINS_ENABLED=false`) cho đến khi có DNS ownership + HTTPS automation. Backup hằng ngày, mục tiêu RPO 24 giờ/RTO 4 giờ; diễn tập restore theo quý. Theo dõi failed jobs, scheduler heartbeat, dung lượng DB/Redis và log rotation. Rollback image chỉ dùng khi schema tương thích; thay đổi phá hủy phải restore backup có kiểm chứng.
