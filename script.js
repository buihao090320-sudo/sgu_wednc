// script.js
window.addEventListener('scroll', function() {
    const header = document.querySelector('.top-bar');
    if (window.scrollY > 50) {
        header.style.padding = '5px 0'; // Thu nhỏ khi cuộn
    } else {
        header.style.padding = '15px 0';
    }
});