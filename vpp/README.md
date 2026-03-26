# 📦 Văn phòng Phẩm Sài Gòn – Hướng dẫn cài đặt XAMPP

## Cấu trúc dự án

```
vpp/
├── index.php               ← Trang chủ
├── san-pham.php            ← Danh sách sản phẩm + tìm kiếm nâng cao
├── chi-tiet.php            ← Chi tiết sản phẩm
├── dang-nhap.php           ← Đăng nhập
├── dang-ky.php             ← Đăng ký
├── dang-xuat.php           ← Đăng xuất
├── them-gio-hang.php       ← Thêm vào giỏ hàng (xử lý POST)
├── gio-hang.php            ← Giỏ hàng + thanh toán
├── dat-hang.php            ← Xử lý đặt hàng (POST)
├── dat-hang-thanh-cong.php ← Trang xác nhận đặt hàng
├── don-hang.php            ← Lịch sử mua hàng
├── vpp_saigon.sql          ← File SQL (import vào phpMyAdmin)
├── includes/
│   ├── config.php          ← Kết nối CSDL + hàm dùng chung
│   ├── header.php          ← Header chung
│   └── footer.php          ← Footer chung
├── assets/
│   ├── css/style.css       ← CSS frontend
│   ├── css/admin.css       ← CSS admin
│   ├── js/main.js          ← JS frontend
│   ├── js/admin.js         ← JS admin
│   └── img/logo.png        ← Logo
├── uploads/                ← Thư mục upload ảnh
└── admin/
    ├── login.php           ← Đăng nhập admin (URL riêng)
    ├── logout.php          ← Đăng xuất admin
    ├── index.php           ← Dashboard tổng quan
    ├── danh-muc.php        ← Quản lý danh mục
    ├── san-pham.php        ← Danh sách sản phẩm
    ├── them-san-pham.php   ← Thêm/sửa sản phẩm
    ├── nhap-hang.php       ← Danh sách phiếu nhập
    ├── them-phieu-nhap.php ← Tạo/sửa phiếu nhập
    ├── xem-phieu-nhap.php  ← Chi tiết phiếu nhập
    ├── quan-ly-gia.php     ← Quản lý giá bán
    ├── lich-su-nhap.php    ← Lịch sử nhập theo SP
    ├── don-hang.php        ← Quản lý đơn đặt hàng
    ├── nguoi-dung.php      ← Quản lý người dùng
    ├── ton-kho.php         ← Tồn kho & Báo cáo
    └── includes/
        ├── header.php      ← Layout header admin
        └── footer.php      ← Layout footer admin
```

---

## 🚀 Các bước cài đặt

### Bước 1 – Cài XAMPP
- Tải XAMPP tại: https://www.apachefriends.org
- Cài đặt và khởi động **Apache** + **MySQL**

### Bước 2 – Copy dự án
```
Copy toàn bộ thư mục vpp/ vào:
C:\xampp\htdocs\vpp\
```

### Bước 3 – Import CSDL
1. Mở trình duyệt, vào: `http://localhost/phpmyadmin`
2. Tạo database mới: **vpp_saigon**  
   *(hoặc để phpMyAdmin tự tạo khi import)*
3. Chọn database **vpp_saigon** → tab **Import**
4. Chọn file `vpp_saigon.sql` → nhấn **Go**

### Bước 4 – Cấu hình kết nối (nếu cần)
Mở file `includes/config.php`, kiểm tra:
```php
define('DB_HOST', 'localhost');   // Giữ nguyên
define('DB_USER', 'root');        // User MySQL của bạn
define('DB_PASS', '');            // Mật khẩu MySQL (XAMPP mặc định để trống)
define('DB_NAME', 'vpp_saigon');  // Tên database
```

### Bước 5 – Truy cập
| Trang | URL |
|-------|-----|
| Website | http://localhost/vpp/ |
| Trang Admin | http://localhost/vpp/admin/login.php |

---

## 🔐 Tài khoản mặc định

### Admin
| Trường | Giá trị |
|--------|---------|
| Email | admin@vppsaigon.vn |
| Mật khẩu | admin123 |

### Khách hàng mẫu
| Trường | Giá trị |
|--------|---------|
| Email | an@gmail.com |
| Mật khẩu | 123456 |

---

## ✅ Checklist chức năng

### End-User (4 điểm)
- [x] Hiển thị sản phẩm theo phân loại (có phân trang 9 sp/trang)
- [x] Chi tiết sản phẩm (thông số kỹ thuật, giá, tồn kho)
- [x] Tìm kiếm cơ bản theo tên sản phẩm
- [x] Tìm kiếm nâng cao (tên + danh mục + khoảng giá)
- [x] Giá bán = giá nhập × (1 + tỉ lệ LN%) — tính đúng
- [x] Giá nhập bình quân gia quyền — cập nhật khi hoàn thành phiếu nhập
- [x] Đăng ký tài khoản (đầy đủ thông tin giao hàng)
- [x] Đăng nhập / Đăng xuất (hiển thị tên tài khoản)
- [x] Giỏ hàng: thêm/bớt/xóa sản phẩm
- [x] Chọn địa chỉ từ tài khoản hoặc nhập địa chỉ mới
- [x] Thanh toán: Tiền mặt / Chuyển khoản (hiện TT ngân hàng) / Trực tuyến
- [x] Tóm tắt đơn hàng sau khi đặt
- [x] Lịch sử mua hàng (mới nhất lên đầu)

### Admin (6 điểm)
- [x] Đăng nhập admin bằng URL riêng (/admin/login.php)
- [x] Dashboard tổng quan + thông tin tài khoản admin
- [x] Quản lý người dùng: thêm, khoá/mở khoá, reset mật khẩu
- [x] Quản lý danh mục: thêm/sửa
- [x] Quản lý sản phẩm: thêm/sửa/xoá (logic đúng: ẩn nếu đã nhập hàng)
- [x] Phiếu nhập hàng: 1 phiếu nhiều SP, lưu nháp, hoàn thành phiếu
- [x] Sửa phiếu nhập (chỉ trước khi hoàn thành)
- [x] Giá nhập bình quân cập nhật khi hoàn thành phiếu
- [x] Quản lý giá bán: xem/sửa tỉ lệ LN, xem lịch sử nhập theo lô
- [x] Quản lý đơn hàng: cập nhật trạng thái 4 mức
- [x] Lọc đơn theo ngày + trạng thái + sắp xếp theo địa chỉ (phường)
- [x] Xem chi tiết từng đơn hàng
- [x] Tra cứu tồn kho tại thời điểm bất kỳ
- [x] Báo cáo nhập-xuất theo khoảng thời gian
- [x] Cảnh báo sắp hết hàng (ngưỡng tùy chỉnh)

### Yêu cầu khác
- [x] DB thiết kế đúng quan hệ 1-nhiều
- [x] Validate form phía client (JS) trước khi submit
- [x] Giao diện đẹp, responsive, dùng logo thương hiệu

---

## 📊 Sơ đồ quan hệ CSDL

```
danh_muc (1) ──── (N) san_pham
san_pham  (1) ──── (N) sp_thong_so
san_pham  (1) ──── (N) chi_tiet_nhap
phieu_nhap(1) ──── (N) chi_tiet_nhap
nguoi_dung(1) ──── (N) gio_hang
nguoi_dung(1) ──── (N) don_hang
don_hang  (1) ──── (N) chi_tiet_don_hang
san_pham  (1) ──── (N) chi_tiet_don_hang
```

---

## 💡 Lưu ý quan trọng
- Sử dụng **đường dẫn tương đối** trong toàn bộ source code ✓
- Mật khẩu lưu bằng **MD5** (theo chuẩn môn học)
- Chạm điểm cộng: Sắp xếp đơn hàng theo địa chỉ giao hàng (phường)
- Test trên **Firefox** hoặc **Google Chrome**
