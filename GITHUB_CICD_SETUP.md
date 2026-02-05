# Hướng dẫn thiết lập CI/CD với GitHub Actions

File workflow đã được tạo tại `.github/workflows/deploy.yml`. Để hệ thống hoạt động, bạn cần cấu hình **Secrets** trên GitHub Repository.

## 1. Chuẩn bị trên Server (Debian)

Đảm bảo bạn đã có SSH Key để GitHub có thể truy cập vào server mà không cần mật khẩu.

1.  **Tạo SSH Key (trên máy cá nhân hoặc server nếu chưa có):**
    ```bash
    ssh-keygen -t ed25519 -C "github-actions"
    ```
    (Nhấn Enter để bỏ qua passphrase nếu muốn tự động hoàn toàn, hoặc setup ssh-agent nếu dùng passphrase - khuyến nghị không dùng passphrase cho CI/CD key).

2.  **Thêm Public Key vào `authorized_keys` trên Server:**
    Copy nội dung file `.pub` (ví dụ `id_ed25519.pub`) và thêm vào file `~/.ssh/authorized_keys` của user mà bạn dùng để deploy trên server.
    ```bash
    cat id_ed25519.pub >> ~/.ssh/authorized_keys
    ```

3.  **Lấy Private Key:**
    Copy nội dung file private key (ví dụ `id_ed25519`) để dùng cho bước tiếp theo.

## 2. Cấu hình GitHub Secrets

Vào Repository của bạn trên GitHub -> **Settings** -> **Secrets and variables** -> **Actions** -> **New repository secret**.

Thêm các secret sau:

| Tên Secret | Giá trị mẫu | Mô tả |
| :--- | :--- | :--- |
| `HOST` | `thezotopia.online` | Địa chỉ IP hoặc Domain của server |
| `USERNAME` | `root` hoặc `admin` | User SSH dùng để deploy (phải có quyền ghi vào thư mục web) |
| `SSH_KEY` | `-----BEGIN OPENSSH PRIVATE KEY...` | Nội dung Private Key bạn đã copy ở bước 1 |
| `DIR` | `/var/www/wwmcontrol` | Đường dẫn tuyệt đối đến thư mục project trên server |
| `PORT` | `22` | (Tùy chọn) Port SSH nếu bạn đổi khác mặc định |

## 3. Kiểm tra Permissions

Đảm bảo user `USERNAME` có quyền ghi vào thư mục `DIR` trên server.
```bash
chown -R username:username /var/www/wwmcontrol
```

## 4. Kích hoạt

Sau khi setup xong, mỗi khi bạn push code lên nhánh `main`, GitHub Actions sẽ tự động chạy workflow `CD`, đăng nhập vào server và thực hiện các lệnh update code, migrate database, build assets.

Bạn có thể theo dõi tiến trình tại tab **Actions** trên GitHub.
