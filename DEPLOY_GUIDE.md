# Hướng dẫn Deploy hệ thống trên Debian 11.3 (Bullseye)

Tài liệu này hướng dẫn cách triển khai hệ thống (Laravel Backend & Next.js Frontend) từ GitHub lên máy chủ chạy Debian 11.3.

## 1. Yêu cầu hệ thống
- Hệ điều hành: Debian 11.3
- PHP 8.2+
- Node.js 18+ & npm
- PostgreSQL 14+
- Nginx
- Composer

## 2. Cài đặt các thành phần cần thiết

### Cập nhật hệ thống
```bash
sudo apt update && sudo apt upgrade -y
```

### Cài đặt PHP 8.2
```bash
sudo apt install -y lsb-release ca-certificates apt-transport-https software-properties-common gnupg2
curl -sS https://packages.sury.org/php/apt.gpg | sudo gpg --dearmor -o /etc/apt/trusted.gpg.d/sury-php.gpg
echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" | sudo tee /etc/apt/sources.list.d/sury-php.list
sudo apt update
sudo apt install -y php8.2 php8.2-fpm php8.2-pgsql php8.2-curl php8.2-xml php8.2-mbstring php8.2-zip php8.2-gd php8.2-intl
```

### Cài đặt PostgreSQL
```bash
sudo apt install -y postgresql postgresql-contrib
sudo systemctl start postgresql
sudo systemctl enable postgresql
```

### Cài đặt Node.js (v20)
```bash
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
```

### Cài đặt Composer
```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### Cài đặt Nginx
```bash
sudo apt install -y nginx
```

## 3. Cấu hình Cơ sở dữ liệu
```bash
sudo -u postgres psql
```
Trong shell psql:
```sql
CREATE DATABASE wwm;
CREATE USER wwm_user WITH PASSWORD 'your_secure_password';
GRANT ALL PRIVILEGES ON DATABASE wwm TO wwm_user;
\q
```

## 4. Triển khai Code từ GitHub

### Clone Repo
```bash
cd /var/www
sudo git clone https://github.com/your-username/your-repo.git wwm
sudo chown -R $USER:$USER /var/www/wwm
cd /var/www/wwm
```

### Cấu hình Backend (Laravel)
```bash
composer install --optimize-autoloader --no-dev
cp .env.example .env
```
Chỉnh sửa `.env`:
```text
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=wwm
DB_USERNAME=wwm_user
DB_PASSWORD=your_secure_password

# Cấu hình Discord
DISCORD_CLIENT_ID=your_client_id
DISCORD_CLIENT_SECRET=your_client_secret
DISCORD_REDIRECT_URI=https://your-domain.com/api/auth/callback/discord
```

Chạy các lệnh setup:
```bash
php artisan key:generate
php artisan storage:link
php artisan migrate --force
php artisan db:seed --force
```

### Cấu hình Frontend (Next.js)
```bash
cd frontend
npm install
# Tạo file .env cho frontend nếu cần
echo "NEXT_PUBLIC_API_URL=https://your-domain.com/api" > .env.local
npm run build
```

## 5. Cấu hình Nginx
Tạo file cấu hình `/etc/nginx/sites-available/wwm`:
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/wwm/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    # Backend API & Static Files
    location /api {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location /storage {
        alias /var/www/wwm/storage/app/public;
    }

    # Frontend (Next.js)
    location / {
        proxy_pass http://localhost:3000;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host $host;
        proxy_cache_bypass $http_upgrade;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```
Kích hoạt cấu hình:
```bash
sudo ln -s /etc/nginx/sites-available/wwm /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

## 6. Chạy Frontend với PM2
```bash
sudo npm install -g pm2
cd /var/www/wwm/frontend
pm2 start npm --name "wwm-frontend" -- start
pm2 save
pm2 startup
```

## 7. Cấp quyền thư mục
```bash
sudo chown -R www-data:www-data /var/www/wwm/storage /var/www/wwm/bootstrap/cache
```

## 8. Bảo mật (SSL với Certbot)
```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d your-domain.com
```

## 9. Lưu ý quan trọng
- Đảm bảo các cổng 80 và 443 đã được mở trên Firewall (ufw hoặc security group của VPS).
- Thay thế `your-domain.com`, `your-username`, `your-repo` và mật khẩu bằng thông tin thực tế của bạn.
- Luôn kiểm tra log tại `/var/www/wwm/storage/logs/laravel.log` nếu gặp lỗi backend.
