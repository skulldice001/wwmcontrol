# Hướng dẫn Cấu hình WebSocket (Reverb) trên Server

Lỗi `WebSocket connection to 'wss://ws-.pusher.com/app/missing-key...'` xuất hiện do thiếu biến môi trường hoặc chưa build lại assets sau khi cấu hình. Dưới đây là các bước khắc phục chi tiết.

## 1. Cấu hình file `.env` trên Server

Mở file `.env` trên server và đảm bảo các biến sau được cấu hình chính xác. Thay thế `your-domain.com` bằng tên miền thực tế của bạn.

```env
# Cấu hình Reverb Server (Backend)
REVERB_APP_ID=888675
REVERB_APP_KEY=ynyx4t6ed6xfbbn9lazq
REVERB_APP_SECRET=rivvmhhxhnt0ney1iuyn
REVERB_HOST="0.0.0.0"
REVERB_PORT=8080
REVERB_SCHEME=http

# Cấu hình Client (Frontend - Vite)
# QUAN TRỌNG: Các biến VITE_ này được nhúng vào file JS khi chạy lệnh 'npm run build'.
# Nếu thay đổi .env, BẮT BUỘC phải chạy lại 'npm run build'.

# Key phải trùng với REVERB_APP_KEY
VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"

# Host phải là tên miền của server (không dùng localhost hay 0.0.0.0 ở đây)
VITE_REVERB_HOST="thezootopia.online"

# Port WebSocket (thường là 443 nếu có SSL/HTTPS, hoặc 8080 nếu chạy trực tiếp)
# Nếu dùng Nginx proxy SSL (https), hãy đặt là 443
VITE_REVERB_PORT=443

# Scheme (https hoặc http)
VITE_REVERB_SCHEME="https"
```

## 2. Build lại Assets (Bắt buộc)

Vì Vite nhúng giá trị biến môi trường `VITE_*` vào file Javascript tại thời điểm build, bạn **phải** chạy lại lệnh build sau khi sửa file `.env`.

Tại thư mục dự án trên server:
```bash
npm install
npm run build
```

*Nếu bạn không thể build trên server:*
1. Sửa file `.env` ở máy local (máy cá nhân) giống hệt cấu hình server (đặc biệt là `VITE_REVERB_HOST` và `VITE_REVERB_SCHEME`).
2. Chạy `npm run build` ở máy local.
3. Upload thư mục `public/build` từ máy local lên server.

## 3. Khởi động Reverb Server

Đảm bảo tiến trình Reverb đang chạy trên server. Bạn nên dùng Supervisor để quản lý tiến trình này.

Lệnh chạy thử (trong terminal):
```bash
php artisan reverb:start
```

Nếu dùng Supervisor, cấu hình mẫu (`/etc/supervisor/conf.d/reverb.conf`):
```ini
[program:reverb]
process_name=%(program_name)s
command=php /path/to/your/project/artisan reverb:start
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/path/to/your/project/storage/logs/reverb.log
```

## 4. Cấu hình Nginx (Reverse Proxy)

Để WebSocket hoạt động mượt mà trên HTTPS (wss://), bạn cần cấu hình Nginx để chuyển tiếp request từ port 443 (hoặc 80) vào port 8080 của Reverb.

Thêm vào block `server` trong cấu hình Nginx của bạn:

```nginx
location /app {
    proxy_pass http://127.0.0.1:8080;
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "Upgrade";
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto $scheme;
}

location /apps {
    proxy_pass http://127.0.0.1:8080;
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "Upgrade";
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto $scheme;
}
```

## 5. Xóa Cache

Cuối cùng, xóa cache để đảm bảo mọi thứ được cập nhật:

```bash
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

## Tóm tắt lỗi "missing-key"
Lỗi này xảy ra khi biến `VITE_REVERB_APP_KEY` không tồn tại trong quá trình `npm run build`. Khi đó, code JS sẽ lấy giá trị mặc định là `'missing-key'` (do mình đã thêm fallback trong `bootstrap.js`), và client sẽ cố kết nối đến server mặc định của Pusher thay vì server Reverb của bạn.
