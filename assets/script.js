// Validation cho form
function validateForm(form, rules) {
    for (let field in rules) {
        let value = form[field]?.value;
        let rule = rules[field];
        
        // Kiểm tra required
        if (rule.required && (!value || value.trim() === '')) {
            alert('Vui lòng nhập ' + rule.label);
            return false;
        }
        
        // Kiểm tra min length
        if (rule.minLength && value.length < rule.minLength) {
            alert(rule.label + ' phải có ít nhất ' + rule.minLength + ' ký tự');
            return false;
        }
        
        // Kiểm tra email
        if (rule.email && !validateEmail(value)) {
            alert('Email không hợp lệ');
            return false;
        }
        
        // Kiểm tra số
        if (rule.number) {
            if (isNaN(value) || value === '') {
                alert(rule.label + ' phải là số');
                return false;
            }
            if (rule.min !== undefined && parseFloat(value) < rule.min) {
                alert(rule.label + ' phải lớn hơn hoặc bằng ' + rule.min);
                return false;
            }
        }
    }
    return true;
}

// Kiểm tra email
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

// Kiểm tra số điện thoại
function validatePhone(phone) {
    const re = /^[0-9]{10,11}$/;
    return re.test(phone);
}

// Xác nhận xóa
function confirmDelete(message) {
    return confirm(message || 'Bạn có chắc muốn xóa?');
}

// Format số tiền
function formatMoney(amount) {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(amount);
}

// Tìm kiếm sản phẩm (AJAX)
function searchProducts(keyword, callback) {
    if (keyword.length < 2) {
        return;
    }
    
    fetch('ajax/search_products.php?q=' + encodeURIComponent(keyword))
        .then(response => response.json())
        .then(data => callback(data))
        .catch(error => console.error('Error:', error));
}

// Tự động tính giá bán dựa trên giá vốn và % lợi nhuận
function calculateSellingPrice(costPrice, profitPercent) {
    return costPrice * (1 + profitPercent / 100);
}