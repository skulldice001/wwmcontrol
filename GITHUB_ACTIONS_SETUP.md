# Hướng dẫn thiết lập GitHub Secrets cho CI/CD

Pipeline CI/CD hiện tại đang gặp lỗi ở bước **Deploy** vì thiếu các thông tin kết nối đến server. Bạn cần thêm các "Secrets" vào GitHub Repository của mình.

## 1. Truy cập vào trang cài đặt Secrets
1. Vào trang GitHub repository của dự án.
2. Chọn tab **Settings** (Cài đặt).
3. Ở menu bên trái, chọn **Secrets and variables** -> **Actions**.
4. Nhấn nút **New repository secret** (màu xanh lá).

## 2. Thêm các Secrets cần thiết
Bạn cần thêm lần lượt các secret sau (tên phải chính xác 100%):

| Tên Secret | Giá trị (Value) | Mô tả |
|------------|-----------------|-------|
| `HOST` | `61.14.234.57` (Hoặc `api.thezotopia.online`) | Địa chỉ IP của server VPS bạn muốn deploy code lên. |
| `USERNAME` | `root` (Thường dùng) | Tên đăng nhập SSH vào server. |
| `KEY` | `-----BEGIN OPENSSH PRIVATE KEY----- ...` | Private Key SSH để đăng nhập vào server (nội dung file `.pem` hoặc `id_rsa`). |
| `PORT` | `22` | Cổng SSH của server (mặc định là 22). |

### Lưu ý về `KEY`:
- Bạn cần copy **toàn bộ** nội dung của file private key, bao gồm cả dòng `-----BEGIN...` và `-----END...`.
- Đảm bảo public key tương ứng đã được thêm vào file `~/.ssh/authorized_keys` trên server.

## 3. Cách tạo SSH Key mới (Nếu bạn chưa có)
Nếu bạn chưa có cặp khóa SSH nào để dùng cho GitHub Actions, hãy làm theo các bước sau trên máy tính của bạn (Windows/Mac/Linux đều được):

### Bước 1: Tạo cặp khóa mới
Mở terminal (PowerShell hoặc Git Bash) và chạy lệnh:
```bash
ssh-keygen -t rsa -b 4096 -C "github-actions-deploy" -f github_deploy_key
```
- Nhấn Enter liên tục để bỏ qua passphrase (để trống).
- Lệnh này sẽ tạo ra 2 file trong thư mục hiện tại:
  1. `github_deploy_key` (Private Key - Dùng cho GitHub Secret)
  2. `github_deploy_key.pub` (Public Key - Dùng cho Server)

### Bước 2: Cấu hình trên Server
1. Copy nội dung file `github_deploy_key.pub`.
2. Đăng nhập vào server của bạn.
3. Chạy lệnh sau để thêm key vào danh sách cho phép:
```bash
echo "noi_dung_public_key_vua_copy" >> ~/.ssh/authorized_keys
```
*Thay `noi_dung_public_key_vua_copy` bằng nội dung thực tế bạn vừa copy.*

### Bước 3: Cấu hình trên GitHub
1. Mở file `github_deploy_key` bằng Notepad hoặc trình soạn thảo văn bản.
2. Copy toàn bộ nội dung.
3. Paste vào GitHub Secret tên là `KEY`.

**Lưu ý quan trọng:** Không bao giờ commit file `github_deploy_key` lên git! Hãy xóa nó sau khi đã cấu hình xong.

## 4. Troubleshooting (Sửa lỗi thường gặp)

### Lỗi: `ssh.ParsePrivateKey: ssh: no key found`
**Nguyên nhân:** Secret `KEY` bạn điền vào GitHub bị sai định dạng, là Public Key, hoặc thiếu dòng Header/Footer.

**GIẢI PHÁP TRIỆT ĐỂ (Tạo key mới chuẩn PEM):**
Nếu bạn làm mãi không được, hãy tạo lại cặp key mới theo chuẩn PEM (tương thích tốt nhất) bằng lệnh sau:

```bash
ssh-keygen -t rsa -b 4096 -m PEM -C "github-actions-deploy" -f github_deploy_key_pem
```
*(Lưu ý tham số `-m PEM` là quan trọng nhất)*

**Cách phân biệt Key ĐÚNG và SAI:**

✅ **ĐÚNG (Private Key - Dùng cho Secret `KEY`):**
Mở file `github_deploy_key_pem` (không có đuôi), nội dung bắt đầu bằng:
```
-----BEGIN RSA PRIVATE KEY-----
MIIEpQIBAAKCAQEA... (rất nhiều dòng mã hóa) ...
...
-----END RSA PRIVATE KEY-----
```
-> **COPY TOÀN BỘ** từ dấu `-` đầu tiên đến dấu `-` cuối cùng.

❌ **SAI (Public Key - Dùng cho Server):**
Mở file `github_deploy_key_pem.pub`, nội dung ngắn và bắt đầu bằng:
```
ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAACAQD...
```
-> Cái này **KHÔNG** được bỏ vào Secret `KEY`. Cái này để bỏ vào `~/.ssh/authorized_keys` trên Server.

### Lỗi: `ssh: handshake failed: ssh: unable to authenticate`
**Nguyên nhân:** Server từ chối key này. Có thể Public Key chưa được thêm vào `authorized_keys` trên server.

**Cách khắc phục:**
1. Đảm bảo bạn đã làm **Bước 2: Cấu hình trên Server** ở mục 3.
2. Kiểm tra file `~/.ssh/authorized_keys` trên server xem đã có dòng nội dung của file `.pub` chưa.

### ❓ Hỏi: Làm sao download file `github_deploy_key_pem` từ Server về máy tính?

Nếu bạn muốn tải file Private Key về máy tính (Windows) để lưu trữ hoặc xem bằng Notepad, bạn có 2 cách đơn giản:

#### Cách 1: Dùng lệnh `scp` (trên Windows PowerShell / CMD)
Mở PowerShell trên máy tính của bạn (không phải trên server) và chạy lệnh:

```powershell
scp root@61.14.234.57:~/github_deploy_key_pem .
```
*(Thay `root` bằng user của bạn nếu khác. Dấu chấm `.` ở cuối nghĩa là tải về thư mục hiện tại)*.

#### Cách 2: Dùng phần mềm WinSCP hoặc FileZilla
1. Tải và cài đặt **WinSCP** (miễn phí).
2. Mở WinSCP, điền thông tin:w
   - **Host name:** `61.14.234.57`
   - **User name:** `root`
   - **Password:** (Mật khẩu root của bạn)
3. Nhấn **Login**.
4. Bên phải là file trên Server. Tìm file `github_deploy_key_pem`.
5. Kéo thả nó sang bên trái (Máy tính của bạn).

#### Cách 3: Copy-Paste (Đơn giản nhất)
1. Trên server, chạy lệnh xem nội dung: `cat github_deploy_key_pem`
2. Copy toàn bộ nội dung hiện ra.
3. Trên máy tính, mở Notepad, Paste vào và lưu lại với tên `github_deploy_key_pem`.

## 5. Kiểm tra lại
Sau khi sửa secret, hãy vào tab **Actions** -> Chọn workflow fail -> **Re-run jobs**.

---

## Giải thích về lỗi "missing server host"
Lỗi này xuất hiện khi GitHub Actions cố gắng chạy lệnh SSH nhưng không tìm thấy biến `HOST`. Điều này có nghĩa là secret `HOST` chưa được tạo hoặc bị để trống.
