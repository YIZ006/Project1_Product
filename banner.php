<link rel="stylesheet" href="/Project1_Product/styles/global.css">
<link rel="stylesheet" href="/Project1_Product/styles/banner.css">
<div class="main-content">
    <div class="category-menu">
        <div class="category-header">Danh mục sản phẩm</div>
        <div class="menu-item">Laptop Gaming-Đồ Họa 
        <div class="submenu-wrapper">

        <div class="submenu-column">
            <div class="submenu-title">LAPTOP MỚI CHÍNH HÃNG</div>
            <div class="submenu-item">Laptop Asus</div>
            <div class="submenu-item">Laptop Dell</div>
            <div class="submenu-item arrow">Laptop Lenovo
                <div class="submenu-level2">
                    <div class="submenu-item">Thinkpad</div>
                    <div class="submenu-item">Ideapad</div>
                    <div class="submenu-item">Legion</div>
                    <div class="submenu-item">Thinkbook</div>
                    <div class="submenu-item">Yoga</div>
                    <div class="submenu-item">Core i3</div>
                    <div class="submenu-item">Core i5</div>
                    <div class="submenu-item">Core i7</div>
                    <div class="submenu-item">Lenovo LOQ</div>
                </div>
            </div>
            <div class="submenu-item">Laptop HP</div>
            <div class="submenu-item">Laptop MSI</div>
            <div class="submenu-item">Laptop Acer</div>
            <div class="submenu-item">Macbook</div>
        </div>
        <div class="submenu-column">
            <div class="submenu-title">CHỌN LAPTOP THEO NHU CẦU</div>
            <div class="submenu-item">Laptop sinh viên - văn phòng</div>
            <div class="submenu-item">Laptop Gaming</div>
            <div class="submenu-item">Laptop đồ họa</div>
            <div class="submenu-item">Laptop mỏng nhẹ</div>
            <div class="submenu-item">Laptop AI</div>
        </div>
    </div>
     </div>
        <div class="menu-item">Laptop Sinh Viên</div>
        <div class="menu-item">Linh kiện PC - Máy tính</div>
        <div class="menu-item">PC để bàn</div>
        <div class="menu-item">Màn Hình Máy Tính</div>
        <div class="menu-item">Phụ Kiện Laptop PC </div>
        <div class="menu-item">Bàn Phím,Chuột </div>
        
    </div>

    <div class="banner-area">
        <div class="banner-container">
            <img src="https://cdn.tgdd.vn/Files/2021/11/26/1400711/asus-voice-up_1280x805-800-resize.jpg" class="banner-slide active" />
            <img src="https://www.anphatpc.com.vn/media/news/1604_qr-tc.jpg" class="banner-slide" />
            <img src="https://www.acervietnam.com.vn/wp-content/uploads/2024/12/Swift-Series-Uu-Dai-Me-Ly-06.12.2024-31.03.2025-Acer-Viet-Nam-KV-1536x864.webp" class="banner-slide" />
            <button class="prev">❮</button>
            <button class="next">❯</button>
        </div>
    </div>
</div>
<script>
        const slides = document.querySelectorAll('.banner-slide');
        const prevBtn = document.querySelector('.prev');
        const nextBtn = document.querySelector('.next');
        let current = 0;

        function showSlide(index) {
            slides.forEach((slide, i) => {
                slide.classList.remove('active');
                if (i === index) slide.classList.add('active');
            });
        }

        function nextSlide() {
            current = (current + 1) % slides.length;
            showSlide(current);
        }

        function prevSlide() {
            current = (current - 1 + slides.length) % slides.length;
            showSlide(current);
        }

        nextBtn.addEventListener('click', nextSlide);
        prevBtn.addEventListener('click', prevSlide);

        setInterval(nextSlide, 3000);
    </script>