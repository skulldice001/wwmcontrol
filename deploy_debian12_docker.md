# Hướng dẫn Deploy hệ thống trên Debian 12 (Bookworm) với Docker

Tài liệu này hướng dẫn chi tiết cách triển khai hệ thống (Laravel Backend & Frontend) lên máy chủ **Debian 12** sử dụng **Docker** và **Docker Compose**.

## 1. Chuẩn bị Server

*   Hệ điều hành: Debian 12 (Bookworm)
*   Quyền truy cập: Root hoặc user có quyền sudo.
*   Port 80 (HTTP) và 443 (HTTPS) chưa được sử dụng (nếu bạn muốn chạy trực tiếp, nếu không hãy đổi port trong file `.env`).

## 2. Cài đặt Docker & Docker Compose

Thực hiện các lệnh sau để cài đặt Docker Engine mới nhất từ repository chính thức:

### Bước 2.1: Cập nhật và cài đặt các gói phụ thuộc
```bash
sudo apt-get update
sudo apt-get install ca-certificates curl gnupg
```

### Bước 2.2: Thêm Docker GPG Key
```bash
sudo install -m 0755 -d /etc/apt/keyrings
curl -fsSL https://download.docker.com/linux/debian/gpg | sudo gpg --dearmor -o /etc/apt/keyrings/docker.gpg
sudo chmod a+r /etc/apt/keyrings/docker.gpg
```

### Bước 2.3: Thêm Repository
```bash
echo \
  "deb [arch="$(dpkg --print-architecture)" signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/debian \
  "$(. /etc/os-release && echo "$VERSION_CODENAME")" stable" | \
  sudo tee /etc/apt/sources.list.d/docker.list > /dev/null
```

### Bước 2.4: Cài đặt Docker Engine
```bash
sudo apt-get update
sudo apt-get install docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin -y
```

### Bước 2.5: Kiểm tra cài đặt
```bash
sudo docker run hello-world
```
Nếu thấy thông báo "Hello from Docker!", bạn đã cài đặt thành công.

## 3. Triển khai Dự án

### Bước 3.1: Clone Source Code
Di chuyển vào thư mục `/var/www` (hoặc thư mục tùy chọn):
```bash
cd /var/www
sudo git clone git@github.com:skulldice001/wwmcontrol.git wwmcontrol
cd wwmcontrol
```

### Bước 3.2: Cấu hình Môi trường (.env)
Copy file mẫu và chỉnh sửa:
```bash
cp .env.example .env
nano .env
```

**Các thông số quan trọng cần thay đổi:**
```ini
APP_ENV=production
APP_DEBUG=false
APP_URL=http://your-domain.com  <-- Thay bằng domain của bạn

# Database (Kết nối đến service 'db' trong Docker)
DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=wwm
DB_USERNAME=wwm_user       <-- Đặt tên user database tùy ý
DB_PASSWORD=secret_pass    <-- Đặt mật khẩu mạnh

# Redis (Kết nối đến service 'redis' trong Docker)
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

# Queue & Cache
QUEUE_CONNECTION=redis
CACHE_STORE=redis
SESSION_DRIVER=redis
```

### Bước 3.3: Khởi động hệ thống
Chạy lệnh sau để build và khởi động các containers (chế độ chạy nền `-d`):
```bash
sudo docker compose -f docker-compose.prod.yml up -d --build
```

Kiểm tra trạng thái các containers:
```bash
sudo docker compose -f docker-compose.prod.yml ps
```

## 4. Thiết lập Ban đầu

Sau khi các container đã chạy (Up), bạn cần thực hiện các bước khởi tạo dữ liệu.

### Bước 4.1: Chạy Migration (Tạo bảng Database)
```bash
sudo docker compose -f docker-compose.prod.yml exec app php artisan migrate --force
```

### Bước 4.2: Tạo Symlink cho Storage (Để hiển thị ảnh upload)
```bash
sudo docker compose -f docker-compose.prod.yml exec app php artisan storage:link
```

### Bước 4.3: Tối ưu hóa Cache (Optional)
Để tăng tốc độ load trang:
```bash
sudo docker compose -f docker-compose.prod.yml exec app php artisan config:cache
sudo docker compose -f docker-compose.prod.yml exec app php artisan route:cache
sudo docker compose -f docker-compose.prod.yml exec app php artisan view:cache
```

## 5. Cập nhật Code mới (Deploy lại)

Khi có code mới trên GitHub, thực hiện quy trình sau để update:

1.  **Kéo code mới về:**
    ```bash
    git pull origin main
    ```

2.  **Build và khởi động lại container:**
    ```bash
    sudo docker compose -f docker-compose.prod.yml up -d --build --force-recreate
    ```
    *(Lệnh này sẽ build lại image nếu có thay đổi trong code hoặc Dockerfile)*

3.  **Chạy lại Migration (nếu có thay đổi DB):**
    ```bash
    sudo docker compose -f docker-compose.prod.yml exec app php artisan migrate --force
    ```

## 6. Xử lý sự cố (Troubleshooting)

*   **Xem logs ứng dụng:**
    ```bash
    sudo docker compose -f docker-compose.prod.yml logs -f app
    ```

*   **Vào trong container để debug:**
    ```bash
    sudo docker compose -f docker-compose.prod.yml exec app bash
    ```

*   **Quyền thư mục (Permission denied):**
    Docker container chạy dưới user `www-data`. Đảm bảo các thư mục `storage` và `bootstrap/cache` có quyền ghi. Dockerfile đã xử lý việc này, nhưng nếu bạn mount volume từ host, có thể cần:
    ```bash
    sudo chown -R www-data:www-data storage bootstrap/cache
    ```

## 7. Cấu hình SSL (HTTPS) với Nginx trên Host (Khuyên dùng)

Để chạy HTTPS, cách đơn giản nhất là cài đặt Nginx trực tiếp trên máy chủ Debian (Host) và dùng nó làm Reverse Proxy trỏ vào Docker container.

1.  **Cài đặt Nginx & Certbot trên Host:**
    ```bash
    sudo apt install nginx python3-certbot-nginx -y
    ```

2.  **Cấu hình Nginx Proxy:**
    Tạo file cấu hình `/etc/nginx/sites-available/wwmcontrol`:
    ```nginx
    server {
        server_name your-domain.com;

        location / {
            proxy_pass http://127.0.0.1:8080; # Giả sử bạn map port 8080 ở docker-compose
            proxy_set_header Host $host;
            proxy_set_header X-Real-IP $remote_addr;
            proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
            proxy_set_header X-Forwarded-Proto $scheme;
        }
    }
    ```
    *(Lưu ý: Bạn cần sửa `docker-compose.prod.yml` để map port khác 80, ví dụ `8080:80`)*

3.  **Kích hoạt HTTPS:**
    ```bash
    sudo ln -s /etc/nginx/sites-available/wwmcontrol /etc/nginx/sites-enabled/
    sudo certbot --nginx -d your-domain.com
    ```
