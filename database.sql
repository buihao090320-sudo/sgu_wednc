-- Tạo database
CREATE DATABASE IF NOT EXISTS web_banhang;
USE web_banhang;

-- Bảng users (người dùng)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    fullname VARCHAR(100),
    email VARCHAR(100),
    phone VARCHAR(20),
    role ENUM('admin', 'user') DEFAULT 'user',
    status TINYINT DEFAULT 1, -- 1: active, 0: locked
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Bảng categories (loại sản phẩm)
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Bảng products (sản phẩm)
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_code VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(200) NOT NULL,
    category_id INT,
    description TEXT,
    unit VARCHAR(50),
    quantity INT DEFAULT 0,
    image VARCHAR(255),
    profit_percent DECIMAL(5,2) DEFAULT 0,
    status TINYINT DEFAULT 1, -- 1: hiển thị, 0: ẩn
    is_deleted TINYINT DEFAULT 0, -- 0: chưa xóa, 1: đã xóa mềm
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- Bảng import_receipts (phiếu nhập)
CREATE TABLE import_receipts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    receipt_code VARCHAR(50) UNIQUE NOT NULL,
    import_date DATE NOT NULL,
    note TEXT,
    status TINYINT DEFAULT 0, -- 0: chưa hoàn thành, 1: đã hoàn thành
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Bảng import_receipt_details (chi tiết phiếu nhập)
CREATE TABLE import_receipt_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    receipt_id INT,
    product_id INT,
    import_price DECIMAL(10,2) NOT NULL,
    quantity INT NOT NULL,
    FOREIGN KEY (receipt_id) REFERENCES import_receipts(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- Bảng orders (đơn hàng)
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_code VARCHAR(50) UNIQUE NOT NULL,
    user_id INT,
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    total_amount DECIMAL(10,2),
    shipping_address TEXT,
    status ENUM('pending', 'confirmed', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    note TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Bảng order_details (chi tiết đơn hàng)
CREATE TABLE order_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT,
    product_id INT,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- Thêm dữ liệu mẫu
INSERT INTO users (username, password, fullname, role, status) VALUES
('admin', MD5('admin123'), 'Quản trị viên', 'admin', 1),
('user1', MD5('user123'), 'Nguyễn Văn A', 'user', 1);

INSERT INTO categories (name, description) VALUES
('Bút chì', '...'),
('...', '...'),
('...n', '...');

INSERT INTO products (product_code, name, category_id, unit, profit_percent, status) VALUES
('SP001', '...', 1, '...', 20, 1),
('SP002', '...', 1, 'cái', 18, 1),
('SP003', '...', 2, 'cái', 15, 1);