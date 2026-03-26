<?php
// includes/image_helper.php
// Dùng chung cho cả upload file lẫn URL

/**
 * Xử lý hình ảnh sản phẩm: ưu tiên file upload, fallback sang URL
 * Trả về: ['success'=>bool, 'image'=>string, 'error'=>string]
 */
function handleProductImage($files_key, $url_input, $old_image = '') {
    $upload_dir = defined('UPLOAD_DIR') ? UPLOAD_DIR : __DIR__ . '/../images/products/';
    $allowed_ext = ['jpg','jpeg','png','webp','gif'];
    $max_size = 2 * 1024 * 1024; // 2MB

    // --- Ưu tiên 1: Upload file từ máy ---
    if (!empty($_FILES[$files_key]['name'])) {
        $file = $_FILES[$files_key];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success'=>false, 'image'=>$old_image, 'error'=>'Lỗi upload file (code '.$file['error'].')'];
        }
        if ($file['size'] > $max_size) {
            return ['success'=>false, 'image'=>$old_image, 'error'=>'Ảnh không được vượt quá 2MB'];
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed_ext)) {
            return ['success'=>false, 'image'=>$old_image, 'error'=>'Định dạng không hợp lệ (chỉ JPG, PNG, WEBP, GIF)'];
        }

        // Kiểm tra MIME type thật sự
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        $allowed_mime = ['image/jpeg','image/png','image/webp','image/gif'];
        if (!in_array($mime, $allowed_mime)) {
            return ['success'=>false, 'image'=>$old_image, 'error'=>'File không phải ảnh hợp lệ'];
        }

        // Tạo thư mục nếu chưa có
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $new_name = 'sp_' . time() . '_' . rand(1000,9999) . '.' . $ext;
        $dest = $upload_dir . $new_name;

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            return ['success'=>false, 'image'=>$old_image, 'error'=>'Không thể lưu file, kiểm tra quyền thư mục'];
        }

        // Xóa ảnh cũ nếu có và không phải URL
        if ($old_image && !isImageUrl($old_image)) {
            $old_path = $upload_dir . $old_image;
            if (file_exists($old_path)) @unlink($old_path);
        }

        return ['success'=>true, 'image'=>$new_name, 'error'=>''];
    }

    // --- Ưu tiên 2: URL từ internet ---
    $url = trim($url_input ?? '');
    if ($url !== '') {
        // Validate URL cơ bản
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return ['success'=>false, 'image'=>$old_image, 'error'=>'URL ảnh không hợp lệ'];
        }
        // Chỉ cho phép http/https
        if (!preg_match('/^https?:\/\//i', $url)) {
            return ['success'=>false, 'image'=>$old_image, 'error'=>'Chỉ chấp nhận URL http/https'];
        }
        // Kiểm tra đuôi URL có phải ảnh không (kiểm tra sơ bộ)
        $url_path = parse_url($url, PHP_URL_PATH);
        $url_ext  = strtolower(pathinfo($url_path ?? '', PATHINFO_EXTENSION));
        // Nếu URL không có đuôi ảnh rõ ràng vẫn cho qua (có thể là CDN)
        // Lưu URL trực tiếp vào DB
        return ['success'=>true, 'image'=>$url, 'error'=>''];
    }

    // Không có gì mới → giữ ảnh cũ
    return ['success'=>true, 'image'=>$old_image, 'error'=>''];
}

/**
 * Kiểm tra image value là URL hay tên file
 */
function isImageUrl($image) {
    return preg_match('/^https?:\/\//i', $image ?? '');
}

/**
 * Lấy src để dùng trong <img>, tương thích cả file lẫn URL
 * $base: đường dẫn tương đối từ file đang render đến gốc project
 *        ví dụ: '' nếu ở root, '../' nếu ở trong /admin/
 */
function getImageSrc($image, $base = '') {
    if (!$image) return '';
    if (isImageUrl($image)) return $image;
    return $base . 'images/products/' . htmlspecialchars($image);
}

/**
 * Render thẻ <img> hoặc emoji fallback
 */
function renderProductImage($image, $icon = '📦', $base = '', $style = '') {
    if ($image) {
        $src = getImageSrc($image, $base);
        $s   = $style ?: 'width:100%;height:100%;object-fit:cover;border-radius:inherit';
        return '<img src="' . htmlspecialchars($src, ENT_QUOTES) . '" alt="" style="' . $s . '" loading="lazy">';
    }
    return '<span style="font-size:3.5rem;line-height:1">' . htmlspecialchars($icon ?? '📦') . '</span>';
}
