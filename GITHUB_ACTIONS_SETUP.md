# Hướng dẫn Thiết lập CI/CD với GitHub Actions

File cấu hình workflow đã được tạo tại: `.github/workflows/ci-cd.yml`.

Để quy trình tự động hóa hoạt động (Build -> Test -> Deploy), bạn cần thực hiện các bước sau trên GitHub Repository.

## 1. Cấu hình Secrets trên GitHub

Truy cập vào Repository của bạn trên GitHub -> **Settings** -> **Secrets and variables** -> **Actions** -> **New repository secret**.

Thêm các secret sau:

| Tên Secret | Giá trị mẫu | Mô tả |
| :--- | :--- | :--- |
| `HOST` | `123.45.67.89` | Địa chỉ IP của Server (hoặc Domain) |
| `USERNAME` | `root` hoặc `ubuntu` | Tên người dùng SSH để truy cập Server |
| `KEY` | `-----BEGIN OPENSSH PRIVATE KEY----- ...` | Nội dung Private Key SSH (file `id_rsa` hoặc `.pem`) |
| `PORT` | `22` | Port SSH (mặc định là 22) |

**Lưu ý về SSH Key:**
- Private Key (`KEY`) phải tương ứng với Public Key (`~/.ssh/authorized_keys`) đã được thêm vào Server.
- Đảm bảo user (`USERNAME`) có quyền ghi vào thư mục dự án trên server.

## 2. Kiểm tra Đường dẫn Deploy

Mở file `.github/workflows/ci-cd.yml` và kiểm tra dòng sau trong `deploy` job:

```yaml
script: |
  # Navigate to project directory (Change this path if needed)
  cd /var/www/wwmcontrol
```

Đảm bảo `/var/www/wwmcontrol` là đường dẫn chính xác tới thư mục dự án trên server của bạn. Nếu khác, hãy sửa lại file này.

## 3. Quy trình hoạt động

Mỗi khi bạn **push code** lên nhánh `main` (hoặc `master`):

1.  **Job Build & Test**: GitHub sẽ tự động tạo máy ảo Ubuntu, cài đặt PHP/Node.js, chạy `composer install`, `npm install`, và chạy `php artisan test`.
2.  **Job Deploy**: Nếu Job 1 thành công, GitHub sẽ kết nối SSH tới Server của bạn và thực hiện:
    *   `git pull` code mới nhất.
    *   `composer install` các gói PHP.
    *   `npm ci` & `npm run build` để build lại assets (JS/CSS).
    *   `php artisan migrate` để cập nhật database.
    *   Restart Queue Worker và Reverb Server.

## 4. Xử lý sự cố thường gặp

### Lỗi: `Host key verification failed`
- Đảm bảo bạn đã thêm đúng Private Key vào GitHub Secret `KEY`.
- Thử thêm `known_hosts` nếu cần (thường `appleboy/ssh-action` tự xử lý, nhưng nếu lỗi hãy kiểm tra lại IP).

### Lỗi: `Permission denied`
- Đảm bảo user SSH (`USERNAME`) có quyền sở hữu thư mục dự án:
  ```bash
  chown -R username:username /var/www/wwmcontrol
  ```

### Lỗi: `Missing VITE_REVERB_APP_KEY` khi deploy
- Đảm bảo file `.env` trên Server đã có đầy đủ các biến môi trường `VITE_` như hướng dẫn trong `SERVER_WEBSOCKET_SETUP.md`. Lệnh `npm run build` trong CD pipeline sẽ dùng file `.env` này.
