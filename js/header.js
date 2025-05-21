document.addEventListener('DOMContentLoaded', function() {
    // Xử lý toggle dropdown
    document.querySelectorAll('.toggle-dropdown').forEach(item => {
        item.addEventListener('click', (e) => {
            e.preventDefault();
            const parent = item.parentElement;
            parent.classList.toggle('active');
        });
    });

    // Đóng dropdown khi click ra ngoài
    document.addEventListener('click', (e) => {
        const dropdowns = document.querySelectorAll('.dropdown');
        if (!e.target.closest('.dropdown')) {
            dropdowns.forEach(dropdown => {
                dropdown.classList.remove('active');
            });
        }
    });

    // Xử lý mở modal đăng nhập
    document.querySelectorAll('.open-login-modal').forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            document.getElementById('login-modal').classList.add('active');
            document.getElementById('register-modal').classList.remove('active');
            document.querySelectorAll('.dropdown').forEach(dropdown => {
                dropdown.classList.remove('active');
            });
        });
    });

    // Xử lý mở modal đăng ký
    document.querySelectorAll('.open-register-modal').forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            document.getElementById('register-modal').classList.add('active');
            document.getElementById('login-modal').classList.remove('active');
            document.querySelectorAll('.dropdown').forEach(dropdown => {
                dropdown.classList.remove('active');
            });
        });
    });

    // Xử lý đóng modal
    document.querySelectorAll('.modal-overlay').forEach(modal => {
        modal.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal-overlay') || e.target.closest('.close-btn')) {
                modal.classList.remove('active');
            }
        });
    });
});