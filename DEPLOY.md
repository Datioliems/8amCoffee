# Hướng dẫn deploy 8AM Coffee (miễn phí để test)

App: Laravel 11 + MySQL + Vite (Three.js/Alpine). Khách quét QR bằng điện thoại → **bắt buộc HTTPS**.

## Biến môi trường quan trọng (production)
```
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:...          # tạo bằng: php artisan key:generate --show
APP_URL=https://<domain>    # QR & asset dùng URL này
FORCE_HTTPS=true            # ép https sau tunnel/PaaS
SESSION_SECURE_COOKIE=true

DB_CONNECTION=mysql
DB_HOST=... DB_PORT=3306 DB_DATABASE=8amcoffee DB_USERNAME=... DB_PASSWORD=...

MAIL_MAILER=smtp MAIL_HOST=... MAIL_PORT=587 MAIL_USERNAME=... MAIL_PASSWORD=...
```

---

## Phương án 1 — Cloudflare Tunnel (free, nhanh nhất, dùng MySQL local)

1. Build asset + chạy app local (giữ MySQL đang chạy):
   ```powershell
   npm run build
   php artisan serve --host=127.0.0.1 --port=8000
   ```
2. Cài cloudflared: `winget install Cloudflare.cloudflared`
3. Mở terminal khác, tạo tunnel:
   ```powershell
   cloudflared tunnel --url http://localhost:8000
   ```
   → nhận URL dạng `https://abc-xyz.trycloudflare.com`
4. Trong `.env` đặt `APP_URL=` URL đó, `FORCE_HTTPS=true`, rồi `php artisan config:clear` và chạy lại `php artisan serve`.
5. Mở URL trên điện thoại để test. In QR bàn từ trang **Bàn & QR** — QR sẽ trỏ đúng URL tunnel.

> URL trycloudflare đổi mỗi lần chạy lại. Free, không giới hạn thời gian.

---

## Phương án 2 — Railway (free trial credit, cloud + MySQL sẵn)

Repo đã có `Dockerfile` + `docker/entrypoint.sh`.

1. Push code lên GitHub.
2. railway.app → New Project → Deploy from GitHub repo (tự nhận Dockerfile).
3. Add plugin **MySQL** (New → Database → MySQL).
4. Ở service app, đặt Variables:
   ```
   APP_KEY=base64:...   APP_ENV=production   APP_DEBUG=false
   APP_URL=https://<app>.up.railway.app   FORCE_HTTPS=true   SESSION_SECURE_COOKIE=true
   DB_CONNECTION=mysql
   DB_HOST=${{MySQL.MYSQLHOST}}  DB_PORT=${{MySQL.MYSQLPORT}}
   DB_DATABASE=${{MySQL.MYSQLDATABASE}}  DB_USERNAME=${{MySQL.MYSQLUSER}}  DB_PASSWORD=${{MySQL.MYSQLPASSWORD}}
   MAIL_MAILER=smtp ... (SMTP)
   RUN_SEED=true        # chỉ lần đầu, sau đó đổi false
   ```
5. Settings → Generate Domain → lấy URL `*.up.railway.app` → cập nhật `APP_URL` → redeploy.
6. Entrypoint tự chạy `migrate` (+ `db:seed` lần đầu). Sau lần đầu đặt `RUN_SEED=false`.

> Render cũng chạy Dockerfile này nhưng cần MySQL ngoài (Aiven/Railway) vì Render free chỉ có Postgres.

## Đăng nhập sau khi seed
`superadmin / Admin@123` (toàn hệ thống) · `admin_8am / Admin@123` (admin chi nhánh).
