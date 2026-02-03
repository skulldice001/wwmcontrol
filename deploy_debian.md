# Hướng dẫn Deploy Laravel trên Debian 12 (Bookworm)

Tài liệu này hướng dẫn chi tiết cách deploy dự án lên server Debian 12 với cấu hình:
- **OS**: Debian 12
- **Web Server**: Nginx
- **PHP**: 8.2
- **Database**: PostgreSQL
- **Node.js**: 20.x

---

## 1. Cập nhật hệ thống và cài đặt công cụ cơ bản

Đăng nhập SSH và chạy:

```bash
apt update && apt upgrade -y
apt install -y git curl unzip zip software-properties-common gnupg2
```

## 2. Cài đặt PHP 8.2 và Extensions

Debian 12 mặc định hỗ trợ PHP 8.2. Cài đặt các module cần thiết cho Laravel:

```bash
apt install -y php8.2 php8.2-fpm php8.2-cli php8.2-common \
php8.2-pgsql php8.2-zip php8.2-gd php8.2-mbstring \
php8.2-curl php8.2-xml php8.2-bcmath php8.2-intl php8.2-sqlite3
```

## 3. Cài đặt PostgreSQL (Database)

```bash
apt install -y postgresql postgresql-contrib
systemctl enable postgresql
systemctl start postgresql
```

**Cấu hình Database và User:**

```bash
# Đăng nhập vào PostgreSQL
sudo -u postgres psql

# Chạy các lệnh SQL sau:
CREATE DATABASE wwmcontrol;
CREATE USER wwmuser WITH PASSWORD '123321';
GRANT ALL PRIVILEGES ON DATABASE wwmcontrol TO wwmuser;
\q
```

## 4. Cài đặt Composer

```bash
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
```

## 5. Cài đặt Node.js (v20 LTS)

```bash
curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
apt install -y nodejs
```

## 6. Deploy Source Code

Giả sử thư mục deploy Laravel là `/var/www/wwmcontrol`.

```bash
cd /var/www
# Clone code Laravel
git clone https://github.com/username/wwmcontrol_V2.git wwmcontrol

cd wwmcontrol

# Cài đặt PHP dependencies
composer install --optimize-autoloader --no-dev

# Cấu hình môi trường
cp .env.example .env
nano .env
```

**Cập nhật .env:**
```ini
APP_ENV=production
APP_DEBUG=false
APP_URL=https://thezotopia.online
FRONTEND_URL=https://thezotopia.online
SANCTUM_STATEFUL_DOMAINS=thezotopia.online
SESSION_DOMAIN=thezotopia.online
APP_KEY=base64:zobDEG/Qb1RpTHqiJJw5Wb2KL5fQhBtUqqr+x4zgW6c=

SESSION_SECURE_COOKIE=true
LOG_CHANNEL=stack
LOG_LEVEL=debug

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US

APP_MAINTENANCE_DRIVER=file
# APP_MAINTENANCE_STORE=database

PHP_CLI_SERVER_WORKERS=4

BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=wwmcontrol
DB_USERNAME=wwmuser
DB_PASSWORD=123321

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_EXPIRE_ON_CLOSE=true
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null
SESSION_SECURE_COOKIE=false
SESSION_SAME_SITE=lax

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database

CACHE_STORE=database
# CACHE_PREFIX=

MEMCACHED_HOST=127.0.0.1

REDIS_CLIENT=phpredis
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_SCHEME=null
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

VITE_APP_NAME="${APP_NAME}"

DISCORD_SSL_VERIFY=false
DISCORD_CLIENT_ID=1457655406372458650
DISCORD_CLIENT_SECRET=***REMOVED***
DISCORD_REDIRECT_URI="https://thezotopia.online/auth/discord/callback"
DISCORD_BOT_TOKEN=***REMOVED***
DISCORD_GUILD_ID=1459088384747376798
DISCORD_ROLE_ID=

```

**Chạy các lệnh Setup:**

```bash
php artisan key:generate
php artisan migrate --force

# Cài đặt Node modules & Build assets
npm install
npm run build

# Phân quyền
chown -R www-data:www-data /var/www/wwmcontrol
chmod -R 775 /var/www/wwmcontrol/storage
chmod -R 775 /var/www/wwmcontrol/bootstrap/cache
```

## 7. Cấu hình Nginx

Chúng ta sẽ dùng 1 server block cho `thezotopia.online` trỏ trực tiếp vào Laravel.

```bash
apt install -y nginx
nano /etc/nginx/sites-available/wwmcontrol
```

**Nội dung config Nginx:**

```nginx
server {
    listen 80;
    server_name thezotopia.online;
    root /var/www/wwmcontrol/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;
    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

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

**Kích hoạt:**

```bash
ln -s /etc/nginx/sites-available/wwmcontrol /etc/nginx/sites-enabled/
rm /etc/nginx/sites-enabled/default
nginx -t
systemctl restart nginx
```

## 8. Cấu hình Supervisor (Queue Worker)

```bash
apt install -y supervisor
nano /etc/supervisor/conf.d/wwmcontrol-worker.conf
```

**Nội dung config Supervisor:**

```ini
[program:wwmcontrol-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/wwmcontrol/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/wwmcontrol/storage/logs/worker.log
stopwaitsecs=3600
```

**Khởi động Worker:**

```bash
supervisorctl reread
supervisorctl update
supervisorctl start wwmcontrol-worker:*
```
