-- ============================================================
-- DATABASE: vpp_saigon
-- Văn phòng Phẩm Sài Gòn
-- ============================================================
CREATE DATABASE IF NOT EXISTS vpp_saigon CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE vpp_saigon;

-- ============================================================
-- BẢNG DANH MỤC SẢN PHẨM
-- ============================================================
CREATE TABLE IF NOT EXISTS danh_muc (
    id       INT AUTO_INCREMENT PRIMARY KEY,
    ten      VARCHAR(100) NOT NULL,
    icon     VARCHAR(10)  NOT NULL DEFAULT '📦',
    mo_ta    TEXT,
    trang_thai TINYINT NOT NULL DEFAULT 1 COMMENT '1=hiện, 0=ẩn',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- BẢNG SẢN PHẨM
-- ============================================================
CREATE TABLE IF NOT EXISTS san_pham (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    danh_muc_id  INT NOT NULL,
    ten          VARCHAR(200) NOT NULL,
    mo_ta        TEXT,
    don_vi_tinh  VARCHAR(30)  DEFAULT 'cái',
    image VARCHAR(255) DEFAULT NULL,
    gia_nhap     DECIMAL(15,2) NOT NULL DEFAULT 0 COMMENT 'giá nhập bình quân hiện tại',
    ti_le_loi_nhuan DECIMAL(5,2) NOT NULL DEFAULT 20 COMMENT 'tỉ lệ % lợi nhuận',
    so_luong_ton INT NOT NULL DEFAULT 0,
    trang_thai   ENUM('hien','an') NOT NULL DEFAULT 'hien' COMMENT 'hien=đang bán, an=ẩn',
    created_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (danh_muc_id) REFERENCES danh_muc(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Thông số kỹ thuật sản phẩm (quan hệ 1-nhiều)
CREATE TABLE IF NOT EXISTS sp_thong_so (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    sp_id      INT NOT NULL,
    ten_thong_so VARCHAR(100) NOT NULL,
    gia_tri    VARCHAR(200) NOT NULL,
    FOREIGN KEY (sp_id) REFERENCES san_pham(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- BẢNG KHÁCH HÀNG / NGƯỜI DÙNG
-- ============================================================
CREATE TABLE IF NOT EXISTS nguoi_dung (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    ho_ten     VARCHAR(100) NOT NULL,
    email      VARCHAR(150) NOT NULL UNIQUE,
    mat_khau   VARCHAR(255) NOT NULL,
    so_dien_thoai VARCHAR(15),
    dia_chi    VARCHAR(255),
    quan_huyen VARCHAR(100),
    tinh_thanh VARCHAR(100),
    trang_thai TINYINT NOT NULL DEFAULT 1 COMMENT '1=hoạt động, 0=bị khóa',
    vai_tro    ENUM('khach_hang','admin') NOT NULL DEFAULT 'khach_hang',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- BẢNG PHIẾU NHẬP HÀNG (1 phiếu - nhiều sản phẩm)
-- ============================================================
CREATE TABLE IF NOT EXISTS phieu_nhap (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    ma_phieu   VARCHAR(30) NOT NULL UNIQUE,
    ngay_nhap  DATE NOT NULL,
    ghi_chu    TEXT,
    trang_thai ENUM('nhap','hoan_thanh') NOT NULL DEFAULT 'nhap',
    tong_tien  DECIMAL(15,2) NOT NULL DEFAULT 0,
    nguoi_tao  INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (nguoi_tao) REFERENCES nguoi_dung(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Chi tiết phiếu nhập (quan hệ 1-nhiều với phiếu_nhập)
CREATE TABLE IF NOT EXISTS chi_tiet_nhap (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    phieu_nhap_id INT NOT NULL,
    sp_id      INT NOT NULL,
    so_luong   INT NOT NULL,
    gia_nhap   DECIMAL(15,2) NOT NULL,
    thanh_tien DECIMAL(15,2) DEFAULT 0,
    FOREIGN KEY (phieu_nhap_id) REFERENCES phieu_nhap(id) ON DELETE CASCADE,
    FOREIGN KEY (sp_id) REFERENCES san_pham(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- BẢNG GIỎ HÀNG
-- ============================================================
CREATE TABLE IF NOT EXISTS gio_hang (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    nguoi_dung_id INT NOT NULL,
    sp_id      INT NOT NULL,
    so_luong   INT NOT NULL DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_user_sp (nguoi_dung_id, sp_id),
    FOREIGN KEY (nguoi_dung_id) REFERENCES nguoi_dung(id) ON DELETE CASCADE,
    FOREIGN KEY (sp_id) REFERENCES san_pham(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- BẢNG ĐƠN HÀNG
-- ============================================================
CREATE TABLE IF NOT EXISTS don_hang (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    ma_don     VARCHAR(30) NOT NULL UNIQUE,
    nguoi_dung_id INT NOT NULL,
    ten_nguoi_nhan VARCHAR(100) NOT NULL,
    so_dien_thoai VARCHAR(15) NOT NULL,
    dia_chi_giao TEXT NOT NULL,
    quan_huyen VARCHAR(100),
    tinh_thanh VARCHAR(100),
    phuong_thuc_tt ENUM('tien_mat','chuyen_khoan','truc_tuyen') NOT NULL DEFAULT 'tien_mat',
    tam_tinh   DECIMAL(15,2) NOT NULL DEFAULT 0,
    phi_giao_hang DECIMAL(15,2) NOT NULL DEFAULT 30000,
    tong_tien  DECIMAL(15,2) NOT NULL DEFAULT 0,
    trang_thai ENUM('cho_xu_ly','da_xac_nhan','da_giao','da_huy') NOT NULL DEFAULT 'cho_xu_ly',
    ghi_chu    TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (nguoi_dung_id) REFERENCES nguoi_dung(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Chi tiết đơn hàng (quan hệ 1-nhiều)
CREATE TABLE IF NOT EXISTS chi_tiet_don_hang (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    don_hang_id INT NOT NULL,
    sp_id      INT NOT NULL,
    ten_sp     VARCHAR(200) NOT NULL,
    so_luong   INT NOT NULL,
    gia_ban    DECIMAL(15,2) NOT NULL,
    thanh_tien DECIMAL(15,2) DEFAULT 0,
    FOREIGN KEY (don_hang_id) REFERENCES don_hang(id) ON DELETE CASCADE,
    FOREIGN KEY (sp_id) REFERENCES san_pham(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- DỮ LIỆU MẪU
-- ============================================================

-- Danh mục
INSERT INTO danh_muc (ten, icon, mo_ta) VALUES
('Bút viết',        '✏️', 'Các loại bút bi, gel, chì, dạ quang'),
('Vở & Sổ tay',    '📒', 'Vở học sinh, sổ tay, tập'),
('Dụng cụ vẽ',     '🎨', 'Màu sắc, bút vẽ, dụng cụ mỹ thuật'),
('Văn phòng',       '🗂️', 'Kẹp, ghim, giấy ghi chú, hộp bút'),
('Giấy & In ấn',   '📄', 'Giấy in A4, giấy photo'),
('Dụng cụ học tập','📐', 'Thước, compa, ê ke, bảng từ'),
('Băng keo & Keo', '🔌', 'Băng keo, keo dán, hồ'),
('Lưu trữ hồ sơ', '📁', 'Bìa lá, hộp hồ sơ, bìa còng');

-- Tài khoản admin
INSERT INTO nguoi_dung (ho_ten, email, mat_khau, so_dien_thoai, dia_chi, quan_huyen, tinh_thanh, vai_tro)
VALUES ('Quản trị viên', 'admin@vppsaigon.vn', MD5('admin123'), '028 3822 1234', '123 Nguyễn Huệ', 'Quận 1', 'Hồ Chí Minh', 'admin');

-- Tài khoản khách hàng mẫu
INSERT INTO nguoi_dung (ho_ten, email, mat_khau, so_dien_thoai, dia_chi, quan_huyen, tinh_thanh, vai_tro)
VALUES ('Nguyễn Văn An', 'an@gmail.com', MD5('123456'), '0901234567', '45 Lê Lợi', 'Quận 1', 'Hồ Chí Minh', 'khach_hang');

-- Sản phẩm mẫu (danh_muc_id 1-8)
INSERT INTO san_pham (danh_muc_id, ten, mo_ta, don_vi_tinh, image, gia_nhap, ti_le_loi_nhuan, so_luong_ton) VALUES
(1,'Bút bi Thiên Long TL-027','Bút bi nắp đậy mực xanh, ngòi 0.7mm. Viết đều mực, không gián đoạn.','cái','san-pham1.png',2200,36,500),
(1,'Bút bi Pentel BK77','Bút bi Pentel ngòi 0.7mm, mực xanh. Mực chảy mượt, không bị nhòe.','cái','san-pham2.png',6500,38,300),
(1,'Bút gel Pilot G2 0.5mm','Bút gel cao cấp Pilot G2, ngòi siêu mịn 0.5mm. Mực gel không nhòe.','cái','san-pham3.png',12000,33,200),
(1,'Bút dạ quang Stabilo Boss','Bút highlight Stabilo Boss. Mực nước không lem, đầu vát 2-5mm.','cái','san-pham4.png',9500,42,350),
(1,'Bút chì gỗ Staedtler HB','Bút chì gỗ cao cấp Staedtler HB, lõi 2mm chống gãy. Hộp 12 cây.','hộp','san-pham5.png',22000,30,400),
(2,'Vở kẻ ngang Hồng Hà 200 trang','Vở học sinh kẻ ngang 200 trang, giấy trắng 70gsm, bìa cứng màu sắc.','cuốn','san-pham6.png',18000,33,600),
(2,'Sổ tay Moleskine Classic A5','Sổ tay cao cấp Moleskine bìa cứng, 240 trang giấy acid-free, bookmark.','cuốn','san-pham7.png',185000,25,80),
(2,'Tập 100 tờ A4 Hòa Bình','Tập vở A4 kẻ ô ly 100 tờ, giấy trắng, bìa cứng màu.','cuốn','san-pham8.png',12000,33,400),
(3,'Màu chì Faber-Castell 24 màu','Hộp màu chì cao cấp 24 màu, lõi 3.8mm mềm mượt, không gãy.','hộp','san-pham9.png',85000,28,150),
(3,'Màu nước Winsor & Newton 12 màu','Bộ màu nước professional 12 màu bánh. Màu trong, độ hòa tan tốt.','bộ','san-pham10.png',155000,22,60),
(4,'Kẹp bướm KANGARO 25mm','Hộp 12 kẹp bướm binder clip 25mm bằng thép mạ kẽm.','hộp','san-pham11.png',12000,50,800),
(4,'Ghim dập DELI 24/6 (1000 cái)','Hộp 1000 ghim dập tiêu chuẩn 24/6, thép mạ đồng.','hộp','san-pham12.png',8500,47,1000),
(4,'Máy dập ghim Maped Essentials','Máy dập ghim 25 tờ, dùng ghim 24/6, tay cầm ergonomic.','cái','san-pham13.png',45000,38,100),
(4,'Giấy ghi chú Post-it 3x3" 12 tập','Bộ 12 tập giấy ghi chú Post-it 3M, mỗi tập 100 tờ.','bộ','san-pham14.png',55000,45,200),
(5,'Giấy in Double A A4 80gsm','Giấy in Double A tiêu chuẩn A4, 80gsm, 500 tờ/ream.','ream','san-pham15.png',62000,27,500),
(5,'Giấy A4 70gsm IKI (500 tờ)','Giấy in IKI 70gsm A4 kinh tế, 500 tờ/ream.','ream','san-pham16.png',48000,25,800),
(6,'Thước kẻ nhựa trong 30cm DELI','Thước kẻ nhựa trong suốt 30cm, vạch khắc rõ ràng.','cái','san-pham17.png',4500,56,500),
(6,'Compa vẽ tròn MAPED Essential','Compa học sinh Maped, chân thép cứng, bán kính tối đa 16cm.','cái','san-pham18.png',22000,45,200),
(7,'Băng keo trong Scotch 3M 18mm','Băng dính trong suốt Scotch 3M, 18mm x 33m. Không vàng.','cuộn','san-pham19.png',18000,44,400),
(7,'Keo UHU stic 8.2g','Keo dán khô UHU Stick 8.2g dạng bôi, không nhòe giấy.','cái','san-pham20.png',14500,38,300),
(8,'Bìa lá A4 trong PVC (100 cái)','Túi bìa lá nhựa PVC trong suốt A4, dày 0.1mm.','hộp','san-pham21.png',28000,43,300),
(8,'Hộp đựng hồ sơ A4 DELI','Hộp đựng hồ sơ A4, có khóa cài, nắp đậy kín.','cái','san-pham22.png',38000,37,150);

-- Thông số kỹ thuật mẫu
INSERT INTO sp_thong_so (sp_id, ten_thong_so, gia_tri) VALUES
(1,'Loại','Bút bi nắp'),(1,'Ngòi','0.7mm'),(1,'Mực','Xanh'),(1,'Thương hiệu','Thiên Long'),
(2,'Loại','Bút bi nắp'),(2,'Ngòi','0.7mm'),(2,'Mực','Xanh/Đen/Đỏ'),(2,'Thương hiệu','Pentel'),
(3,'Loại','Bút gel bơm'),(3,'Ngòi','0.5mm'),(3,'Thương hiệu','Pilot'),(3,'Đặc điểm','Chống nhòe'),
(6,'Số trang','200 trang'),(6,'Kẻ','Kẻ ngang'),(6,'Giấy','70gsm'),(6,'Kích thước','A5'),
(9,'Số màu','24 màu'),(9,'Lõi','3.8mm'),(9,'Thương hiệu','Faber-Castell'),
(15,'Kích thước','A4 (210x297mm)'),(15,'Định lượng','80gsm'),(15,'Số tờ','500 tờ/ream');

-- Phiếu nhập mẫu (đã hoàn thành)
INSERT INTO phieu_nhap (ma_phieu, ngay_nhap, ghi_chu, trang_thai, tong_tien, nguoi_tao) VALUES
('PN20240101001', '2024-01-01', 'Nhập hàng đầu năm 2024', 'hoan_thanh', 4500000, 1);

INSERT INTO chi_tiet_nhap (phieu_nhap_id, sp_id, so_luong, gia_nhap, thanh_tien) VALUES
(1,1,500,2200,1100000),(1,2,300,6500,1950000),(1,3,200,12000,2400000),(1,6,600,18000,10800000),(1,15,500,62000,31000000);
