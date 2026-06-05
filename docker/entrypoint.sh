#!/usr/bin/env bash
set -e

# Host (Railway/Render) cấp cổng qua $PORT — cho Apache lắng nghe đúng cổng.
PORT="${PORT:-8080}"
sed -ri "s/^Listen 80\$/Listen ${PORT}/" /etc/apache2/ports.conf || true
sed -ri "s/:80>/:${PORT}>/" /etc/apache2/sites-available/000-default.conf || true

# Đảm bảo CHỈ một MPM (prefork) ngay lúc chạy — phòng image cache cũ.
rm -f /etc/apache2/mods-enabled/mpm_event.* /etc/apache2/mods-enabled/mpm_worker.* 2>/dev/null || true
a2enmod mpm_prefork rewrite 2>/dev/null || true
echo ">> Apache PORT=${PORT} | MPM: $(ls /etc/apache2/mods-enabled/ | grep -i mpm | tr '\n' ' ')"

# Dọn mọi config cache cũ (phòng image/commit có sẵn bootstrap/cache/config.php)
# để chắc chắn migrate đọc DB_* từ biến môi trường runtime.
php artisan config:clear || true

# In ra cấu hình DB THẬT mà app đang dùng (ẩn mật khẩu) — để chẩn đoán kết nối.
php -r '
  $h = getenv("DB_HOST") ?: "(rong -> se roi ve 127.0.0.1)";
  $p = getenv("DB_PORT") ?: "(rong)";
  $d = getenv("DB_DATABASE") ?: "(rong)";
  $u = getenv("DB_USERNAME") ?: "(rong)";
  $w = getenv("DB_PASSWORD");
  echo ">> DB cau hinh: HOST=$h PORT=$p DB=$d USER=$u PASS=" . ($w ? "(co " . strlen($w) . " ky tu)" : "(RONG!)") . "\n";
'

# Chạy migration với retry — MySQL trên Railway có thể lên chậm hơn app.
MIGRATED=0
for i in $(seq 1 20); do
    if php artisan migrate --force; then MIGRATED=1; break; fi
    echo ">> DB chưa sẵn sàng hoặc lỗi migrate — thử lại sau 3s ($i/20)..."
    sleep 3
done

# Seed dữ liệu lần đầu nếu RUN_SEED=true (chỉ khi migrate thành công).
if [ "$MIGRATED" = "1" ] && [ "${RUN_SEED}" = "true" ]; then
    php artisan db:seed --force || true
fi

# Cache cấu hình cho nhanh.
php artisan config:cache || true
php artisan route:cache  || true
php artisan view:cache   || true

# Cấp quyền ghi cho storage/cache (Apache chạy bằng www-data) — sau khi các lệnh
# artisan chạy bằng root có thể tạo file log/cache thuộc root.
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true

exec apache2-foreground
