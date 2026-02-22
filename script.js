// ===== DANH SÁCH SẢN PHẨM =====
const products = [
    {
        id: 1,
        name: "Táo",
        price: 25000,
        image: "https://images.unsplash.com/photo-1567306226416-28f0efdc88ce"
    },
    {
        id: 2,
        name: "Chuối",
        price: 15000,
        image: "https://images.unsplash.com/photo-1574226516831-e1dff420e12e"
    },
    {
        id: 3,
        name: "Xoài",
        price: 30000,
        image: "https://images.unsplash.com/photo-1553279768-865429fa0078"
    }
];

let cart = JSON.parse(localStorage.getItem("cart")) || [];

// ===== HIỂN THỊ SẢN PHẨM =====
function renderProducts() {
    const productList = document.getElementById("product-list");
    productList.innerHTML = "";

    products.forEach(product => {
        productList.innerHTML += `
            <div class="product">
                <img src="${product.image}">
                <h3>${product.name}</h3>
                <p>${product.price} đ</p>
                <button onclick="addToCart(${product.id})">Thêm vào giỏ</button>
            </div>
        `;
    });
}

// ===== THÊM VÀO GIỎ =====
function addToCart(id) {
    const product = products.find(p => p.id === id);
    const existing = cart.find(item => item.id === id);

    if (existing) {
        existing.quantity++;
    } else {
        cart.push({ ...product, quantity: 1 });
    }

    saveCart();
    renderCart();
}

// ===== HIỂN THỊ GIỎ =====
function renderCart() {
    const cartItems = document.getElementById("cart-items");
    cartItems.innerHTML = "";

    let total = 0;

    cart.forEach(item => {
        total += item.price * item.quantity;

        cartItems.innerHTML += `
            <div class="cart-item">
                <span>${item.name} x${item.quantity}</span>
                <div>
                    <button onclick="changeQuantity(${item.id}, -1)">-</button>
                    <button onclick="changeQuantity(${item.id}, 1)">+</button>
                    <button onclick="removeItem(${item.id})">X</button>
                </div>
            </div>
        `;
    });

    document.getElementById("total").textContent = total;
}

// ===== TĂNG GIẢM SỐ LƯỢNG =====
function changeQuantity(id, amount) {
    const item = cart.find(i => i.id === id);
    item.quantity += amount;

    if (item.quantity <= 0) {
        removeItem(id);
    }

    saveCart();
    renderCart();
}

// ===== XÓA SẢN PHẨM =====
function removeItem(id) {
    cart = cart.filter(item => item.id !== id);
    saveCart();
    renderCart();
}

// ===== LƯU GIỎ =====
function saveCart() {
    localStorage.setItem("cart", JSON.stringify(cart));
}

// ===== KHỞI ĐỘNG =====
renderProducts();
renderCart();