# Chuyển NukeViet → Laravel (Chứng điệp thọ giới)

## URL được giữ nguyên

| URL cũ (đã in trong QR / đang chạy) | Xử lý trong Laravel |
|---|---|
| `/official/{alias-giới-đàn}/{họ-tên}-{id}` (mẫu QR) | `OfficialController@show` — công khai, tra cứu **chỉ theo `id`**, nên slug/alias lệch chữ hoa-thường vẫn mở được; chấp nhận `/` cuối hoặc `.html`; `?print=1` là chế độ in |
| `/index.php?nv=official&op={giới-đàn}/{họ-tên}-{id}` | 301 sang URL đẹp tương ứng |
| `/` và mọi link tra cứu cũ khác (`/official`, `/official/search`, `/gioi-dan`, `/official/{giới-đàn}`...) | Không còn trang công khai; `/` chuyển sang `/admin/login` |
| `/vi/...` | 301 bỏ tiền tố |
| `/api_public.php` | Đã bỏ (404) |
| `/uploads/...`, `/assets/...` | thư mục thật trong `public/` (đã copy từ NukeViet) |

QR sinh ra vẫn dùng domain cố định `QR_BASE_URL=https://chungdiepthogioi.ghpgvn.vn` (không đổi theo môi trường).
Thuật toán slug (`App\Support\Alias`) là bản port của `change_alias()` + bảng `romanize` của NukeViet.

## Cơ sở dữ liệu

Dùng nguyên các bảng `nv4_vi_official_*` (không đổi schema) → có thể trỏ thẳng vào DB production.
Session/cache dùng `file` để không phải tạo thêm bảng trong DB cũ.

## Triển khai

1. Document root = `public/`.
2. Giữ thư mục `uploads/` (1.4 GB) ở chỗ cũ, tạo symlink `public/uploads` → thư mục đó (và `public/assets`).
3. `.env`: `DB_*`, `APP_URL`, `QR_BASE_URL`, `APP_ENV=production`, `APP_DEBUG=false`; chạy `php artisan key:generate`, `php artisan config:cache route:cache view:cache`.
4. Không để lại `.htaccess` kiểu NukeViet ở root cũ nữa (nó rewrite mọi thứ về `index.php` cũ).

## Chưa chuyển

- Trang quản trị NukeViet (thêm/sửa/import chứng điệp, user, menu, banner).
- Trang `detail-2` (bản dành cho admin, ký tên theo từng loại thọ giới).
