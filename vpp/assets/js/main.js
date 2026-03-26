// assets/js/main.js
// Toast helper
function showToast(msg) {
    let t = document.querySelector('.toast');
    if (!t) { t = document.createElement('div'); t.className = 'toast'; document.body.appendChild(t); }
    t.textContent = msg;
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 2800);
}

// Auto-show session flash messages as toast
document.addEventListener('DOMContentLoaded', () => {
    const alert = document.querySelector('.alert');
    if (alert) {
        const msg = alert.textContent.trim();
        if (msg) setTimeout(() => showToast(msg), 300);
    }
});
