# Hướng dẫn Docker – WWMControl

## Kiến trúc

```
┌─────────────────────────────────────────────────────────────┐
│  Internet                                                   │
│       ↓ :80 (HTTP)           ↓ :8080 (WebSocket)           │
├──────────────┬──────────────────────────────────────────────┤
│  app         │  reverb                                      │
│  Nginx+PHP   │  Laravel Reverb WS server                    │
├──────────────┴──────────────────────────────────────────────┤
│  queue             scheduler                                │
│  queue:work        schedule:work                            │
├──────────────┬──────────────────────────────────────────────┤
│  redis             db (PostgreSQL — tùy chọn)               │
└──────────────┴──────────────────────────────────────────────┘
```

| Container   | Mô tả |
|-------------|-------|
| `app`       | Nginx + PHP-FPM, phục vụ HTTP requests |
| `reverb`    | WebSocket server cho tính năng realtime (poker lobby, lottery) |
| `queue`     | Xử lý background jobs |
| `scheduler` | Chạy các scheduled tasks (lottery draw, daily bonus, ...) |
| `redis`     | Cache, sessions, queue backend |
| `db`        | PostgreSQL — **tuỳ chọn**, bỏ qua nếu dùng DB ngoài |

---

## Yêu cầu

- Docker Engine ≥ 24
- Docker Compose plugin (`docker compose` không phải `docker-compose`)
- Git (để clone repo)

---

## Lần đầu triển khai

### 1. Clone repository

```bash
git clone <repo-url> wwmcontrol
cd wwmcontrol
```

### 2. Tạo file `.env`

```bash
cp .env.docker.example .env
```

Mở `.env` và điền đầy đủ các giá trị:

| Biến | Bắt buộc | Ghi chú |
|------|----------|---------|
| `APP_KEY` | ✓ | Để trống → script tự sinh |
| `APP_URL` | ✓ | VD: `https://api.thezootopia.online` |
| `FRONTEND_URL` | ✓ | VD: `https://thezootopia.online` |
| `DB_HOST` | ✓ | `db` (container) hoặc IP server ngoài |
| `DB_DATABASE` | ✓ | Tên database |
| `DB_USERNAME` | ✓ | |
| `DB_PASSWORD` | ✓ | Đặt mật khẩu mạnh |
| `REVERB_APP_KEY` | ✓ | Chuỗi bất kỳ (key xác thực WS) |
| `REVERB_APP_SECRET` | ✓ | Chuỗi bất kỳ (secret) |
| `VITE_REVERB_APP_KEY` | ✓ | Phải **giống** `REVERB_APP_KEY` |
| `VITE_REVERB_HOST` | ✓ | Hostname public để browser kết nối WS |
| `DISCORD_BOT_TOKEN` | ✓ | Token bot Discord |
| `DISCORD_GUILD_ID` | ✓ | ID server Discord |
| `DISCORD_CLIENT_ID/SECRET` | ✓ | OAuth Discord login |

> **Lưu ý WebSocket (Reverb):**
> - `REVERB_HOST=reverb` (trong docker-compose) là tên container nội bộ — **không thay đổi**
> - `VITE_REVERB_HOST` là hostname **public** mà browser dùng để kết nối, thường giống với `APP_URL` host

### 3. Chạy deploy script

```bash
bash deploy.sh
```

Script sẽ:
1. Kiểm tra và tạo `APP_KEY` nếu thiếu
2. Build Docker image (bao gồm `npm run build` với Vite)
3. Khởi động tất cả services
4. Chờ app sẵn sàng và báo cáo kết quả

---

## Cập nhật code (redeploy)

```bash
git pull
bash deploy.sh
```

Nếu chỉ đổi PHP/Blade (không đổi JS/CSS):

```bash
bash deploy.sh --skip-build   # dùng lại image cũ, chỉ restart
```

> Migration sẽ tự chạy mỗi lần `app` container khởi động.

---

## Sử dụng DB ngoài (external PostgreSQL)

Nếu đã có PostgreSQL tại `61.14.234.57`:

Trong `.env`:
```
DB_HOST=61.14.234.57
DB_PORT=5432
```

Trong `docker-compose.prod.yml`, **comment out** service `db`:
```yaml
#  db:
#    image: postgres:14-alpine
#    ...
```

Cũng bỏ `db:` khỏi `depends_on` trong các service khác nếu cần.

---

## Các lệnh thường dùng

### Xem logs

```bash
# Tất cả services
docker compose -f docker-compose.prod.yml logs -f

# Chỉ app
docker compose -f docker-compose.prod.yml logs -f app

# Chỉ queue worker
docker compose -f docker-compose.prod.yml logs -f queue
```

### Shell vào container

```bash
docker compose -f docker-compose.prod.yml exec app bash
```

### Chạy Artisan commands

```bash
# Chạy migration
docker compose -f docker-compose.prod.yml exec app php artisan migrate

# Import bài viết từ Discord
docker compose -f docker-compose.prod.yml exec app php artisan library:import-discord

# Download ảnh thư viện về server
docker compose -f docker-compose.prod.yml exec app php artisan library:download-images

# Xem danh sách commands
docker compose -f docker-compose.prod.yml exec app php artisan list
```

### Khởi động lại một service

```bash
docker compose -f docker-compose.prod.yml restart app
docker compose -f docker-compose.prod.yml restart queue
docker compose -f docker-compose.prod.yml restart reverb
```

### Dừng tất cả

```bash
bash deploy.sh --down
# hoặc
docker compose -f docker-compose.prod.yml down
```

### Dừng và xóa volumes (⚠️ xoá dữ liệu)

```bash
docker compose -f docker-compose.prod.yml down -v
```

---

## Lưu ảnh thư viện (library images)

Ảnh tải về từ Discord được lưu trong `public/img/library/` bên trong container. Chúng **không được persist** vào Docker volume — khi rebuild image, ảnh đã commit vào git sẽ có sẵn trong image.

Để ảnh mới tải xuống không bị mất sau khi rebuild:

```bash
# Commit ảnh mới vào git sau khi chạy download-images
docker compose -f docker-compose.prod.yml exec app php artisan library:download-images

# Sao chép ra ngoài container
docker cp wwmcontrol-app-1:/var/www/public/img/library ./public/img/library

# Commit vào git
git add public/img/library/
git commit -m "chore: sync library images"
git push
```

---

## Biến môi trường cho Reverb (WebSocket)

Có hai nhóm biến liên quan đến Reverb:

### Server-side (PHP)

```env
REVERB_APP_ID=the-zoo-app
REVERB_APP_KEY=my_key
REVERB_APP_SECRET=my_secret
REVERB_HOST=reverb      # tên service trong docker-compose (KHÔNG đổi)
REVERB_PORT=8080
REVERB_SCHEME=http
```

### Client-side (JavaScript — bake vào bundle lúc build)

```env
VITE_REVERB_APP_KEY=my_key          # giống REVERB_APP_KEY
VITE_REVERB_HOST=api.thezootopia.online   # public hostname
VITE_REVERB_PORT=8080
VITE_REVERB_SCHEME=https
```

> Vì `VITE_*` được bake vào JS bundle **tại thời điểm build**, mỗi khi thay đổi các biến này cần rebuild image (`bash deploy.sh`).

---

## HTTPS / SSL

Docker Compose hiện chỉ expose port 80 (HTTP) và 8080 (WS). Để chạy HTTPS:

**Khuyến nghị:** Đặt Nginx Proxy Manager hoặc Traefik phía trước và forward về container `app:80` và `reverb:8080`.

Sau khi có SSL:
- `VITE_REVERB_SCHEME=https` → kết nối WS qua `wss://`
- `VITE_REVERB_PORT=443` (nếu proxy WS qua cổng 443)

---

## Cấu trúc file Docker

```
wwmcontrol/
├── Dockerfile                    # Multi-stage build (Node → PHP-FPM)
├── docker-compose.prod.yml       # Production services
├── .env.docker.example           # Template môi trường (copy → .env)
├── deploy.sh                     # Script deploy tự động
└── docker/
    ├── entrypoint.sh             # Startup script (migrate, cache, storage:link)
    ├── nginx/
    │   └── conf.d/app.conf       # Nginx config cho Laravel
    └── supervisor/
        └── supervisord.conf      # Supervisor: chạy Nginx + PHP-FPM
```
