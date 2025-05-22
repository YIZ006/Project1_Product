<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include(__DIR__ . '/../connect.php');

if ($connect->connect_error) {
    die("Kết nối thất bại: " . $connect->connect_error);
}

// Xử lý thêm sản phẩm vào giỏ hàng
if (isset($_GET['add_to_cart']) && isset($_GET['masp'])) {
    $masp = (int)$_GET['masp'];
    $makh = isset($_SESSION['makh']) ? (int)$_SESSION['makh'] : null;

    if (!$makh) {
        header("Location: /Project1_Product/customer/login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
        exit();
    }

    // Kiểm tra giỏ hàng
    $stmt_gh = $connect->prepare("SELECT magh FROM giohang WHERE makh = ? AND hieuluc = 1");
    $stmt_gh->bind_param("i", $makh);
    $stmt_gh->execute();
    $result_gh = $stmt_gh->get_result();

    if ($result_gh->num_rows == 0) {
        $stmt_insert_gh = $connect->prepare("INSERT INTO giohang (makh, ngaytao, hieuluc, tongsl, tongtien) VALUES (?, CURDATE(), 1, 0, 0)");
        $stmt_insert_gh->bind_param("i", $makh);
        $stmt_insert_gh->execute();
        $magh = $connect->insert_id;
        $stmt_insert_gh->close();
    } else {
        $giohang = $result_gh->fetch_assoc();
        $magh = $giohang['magh'];
    }
    $stmt_gh->close();

    // Kiểm tra chi tiết giỏ hàng
    $stmt_ct = $connect->prepare("SELECT soluong FROM chitietgiohang WHERE magh = ? AND masp = ?");
    $stmt_ct->bind_param("ii", $magh, $masp);
    $stmt_ct->execute();
    $result_ct = $stmt_ct->get_result();

    if ($result_ct->num_rows > 0) {
        $ct = $result_ct->fetch_assoc();
        $new_soluong = $ct['soluong'] + 1;
        $stmt_update_ct = $connect->prepare("UPDATE chitietgiohang SET soluong = ? WHERE magh = ? AND masp = ?");
        $stmt_update_ct->bind_param("iii", $new_soluong, $magh, $masp);
        $stmt_update_ct->execute();
        $stmt_update_ct->close();
    } else {
        $stmt_insert_ct = $connect->prepare("INSERT INTO chitietgiohang (magh, masp, soluong) VALUES (?, ?, 1)");
        $stmt_insert_ct->bind_param("ii", $magh, $masp);
        $stmt_insert_ct->execute();
        $stmt_insert_ct->close();
    }
    $stmt_ct->close();

    // Cập nhật tổng số lượng và tổng tiền
    $stmt_update_gh = $connect->prepare("
        UPDATE giohang 
        SET tongsl = (SELECT SUM(soluong) FROM chitietgiohang WHERE magh = ?), 
            tongtien = (
                SELECT SUM(ct.soluong * sp.giaban) 
                FROM chitietgiohang ct 
                JOIN sanpham sp ON ct.masp = sp.masp 
                WHERE ct.magh = ?
            )
        WHERE magh = ?
    ");
    $stmt_update_gh->bind_param("iii", $magh, $magh, $magh);
    $stmt_update_gh->execute();
    $stmt_update_gh->close();

    header("Location: chitiet.php?id=$masp");
    exit();
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Danh Sách Sản Phẩm</title>
    <link rel="stylesheet" href="/Project1_Product/styles/product/product_display.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .product-card {
            transition: opacity 0.5s ease-in-out, transform 0.5s ease-in-out;
        }

        .product-card-enter {
            opacity: 0;
            transform: translateY(20px);
        }

        .product-card-enter-active {
            opacity: 1;
            transform: translateY(0);
        }

        .add-to-cart-btn {
            display: inline-block;
            padding: 5px 10px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }

        .add-to-cart-btn:hover {
            background-color: #45a049;
        }

        .add-to-cart-btn.clicked {
            animation: pulse 0.5s;
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.1);
            }

            100% {
                transform: scale(1);
            }
        }

        .toast {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background-color: rgb(106, 220, 255);
            color: white;
            padding: 12px 24px;
            border-radius: 5px;
            opacity: 0;
            transition: opacity 0.3s ease-in-out;
            z-index: 1000;
        }

        .toast.show {
            opacity: 1;
        }
    </style>
</head>

<body class="bg-gray-100 dark:bg-gray-900 transition-colors duration-300">
    <div class="container mx-auto p-4">
        <div id="product-container">
            <?php
            // Lấy 5 danh mục sản phẩm đầu tiên
            $sql_loai = "SELECT * FROM loaisanpham LIMIT 5";
            $result_loai = mysqli_query($connect, $sql_loai);
            $total_loai = mysqli_num_rows(mysqli_query($connect, "SELECT * FROM loaisanpham"));
            $loaded_loai = mysqli_num_rows($result_loai);
            ?>
            <?php if (mysqli_num_rows($result_loai) > 0): ?>
                <?php while ($loai = mysqli_fetch_assoc($result_loai)): ?>
                    <h2 class="text-2xl font-semibold text-gray-800 dark:text-white my-4"><?= htmlspecialchars($loai['tenloai']) ?></h2>
                    <?php
                    $maloai = $loai['maloai'];
                    $sql_sp = "
                        SELECT masp, tensp, giaban, soluongton, hinhanh, mota, giamgia
                        FROM sanpham
                        WHERE maloai = $maloai
                    ";
                    $result_sp = mysqli_query($connect, $sql_sp);
                    ?>
                    <?php if (mysqli_num_rows($result_sp) > 0): ?>
                        <div class="product-list grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4">
                            <?php while ($row = mysqli_fetch_assoc($result_sp)): ?>
                                <div class="product-card bg-white dark:bg-gray-800 p-4 rounded shadow">
                                    <?php if ($row['giamgia'] > 0): ?>
                                        <div class="discount-label bg-red-500 text-white px-2 py-1 rounded absolute">Giảm <?= htmlspecialchars($row['giamgia']) ?>%</div>
                                    <?php endif; ?>
                                    <a href="/Project1_Product/product/sanpham/chitietsp.php?masp=<?= $row['masp'] ?>">
                                        <img src="/Project1_Product/uploads/<?= htmlspecialchars($row['hinhanh']) ?>" alt="<?= htmlspecialchars($row['tensp']) ?>" class="product-img w-full h-48 object-cover rounded">
                                    </a>
                                    <h3 class="product-name text-lg font-semibold text-gray-800 dark:text-white mt-2">
                                        <?= htmlspecialchars($row['tensp']) ?>
                                    </h3>
                                    <div class="price flex items-center space-x-2">
                                        <span class="current-price text-red-600 font-bold"><?= number_format($row['giaban'], 0, ',', '.') ?>đ</span>
                                        <?php if ($row['giamgia'] > 0): ?>
                                            <span class="original-price text-gray-500 line-through"><?= number_format($row['giaban'] * (1 + $row['giamgia'] / 100), 0, ',', '.') ?>đ</span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="small-note text-gray-600 dark:text-gray-300">Smember giảm thêm đến 308.000đ</p>
                                    <p class="installment text-gray-600 dark:text-gray-300">Trả góp 0% không phí chuyển đổi</p>
                                    <div class="footer flex justify-between items-center mt-2">
                                        <div class="stars text-yellow-400">⭐️⭐️⭐️⭐️⭐️</div>
                                        <button class="add-to-cart-btn" data-masp="<?= $row['masp'] ?>">🛒</button>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-gray-600 dark:text-gray-300">Không có sản phẩm trong danh mục này.</p>
                    <?php endif; ?>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="text-gray-600 dark:text-gray-300">Không có loại sản phẩm nào.</p>
            <?php endif; ?>
        </div>
        <?php if ($loaded_loai < $total_loai): ?>
            <div class="text-center mt-6">
                <button id="loadMoreButton" class="px-6 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 transition duration-300">Xem Thêm</button>
            </div>
        <?php endif; ?>
    </div>

    <script>
        let offset = 5;
        const limit = 5;
        const totalLoai = <?= $total_loai ?>;
        const loadMoreButton = document.getElementById('loadMoreButton');
        const productContainer = document.getElementById('product-container');

        // Hàm hiển thị toast
        function showToast(message, type = 'success') {
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            toast.textContent = message;
            document.body.appendChild(toast);
            setTimeout(() => {
                toast.classList.add('show');
            }, 100);
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => {
                    toast.remove();
                }, 300);
            }, 3000);
        }

        // Xử lý nút Xem thêm
        if (loadMoreButton) {
            loadMoreButton.addEventListener('click', function() {
                fetch(`/Project1_Product/product/loadmore.php?offset=${offset}&limit=${limit}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.categories.length > 0) {
                            data.categories.forEach(category => {
                                let html = `<h2 class="text-2xl font-semibold text-gray-800 dark:text-white my-4">${category.tenloai}</h2>`;
                                if (category.products.length > 0) {
                                    html += `<div class="product-list grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4">`;
                                    category.products.forEach(product => {
                                        html += `
                                            <div class="product-card bg-white dark:bg-gray-800 p-4 rounded shadow product-card-enter">
                                                ${product.giamgia > 0 ? `<div class="discount-label bg-red-500 text-white px-2 py-1 rounded absolute">Giảm ${product.giamgia}%</div>` : ''}
                                                <a href="/Project1_Product/product/sanpham/chitietsp.php?masp=${product.masp}">
                                                    <img src="/Project1_Product/uploads/${product.hinhanh}" alt="${product.tensp}" class="product-img w-full h-48 object-cover rounded">
                                                </a>
                                                <h3 class="product-name text-lg font-semibold text-gray-800 dark:text-white mt-2">${product.tensp}</h3>
                                                <div class="price flex items-center space-x-2">
                                                    <span class="current-price text-red-600 font-bold">${new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(product.giaban)}</span>
                                                    ${product.giamgia > 0 ? `<span class="original-price text-gray-500 line-through">${new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(product.giaban * (1 + product.giamgia / 100))}</span>` : ''}
                                                </div>
                                                <p class="small-note text-gray-600 dark:text-gray-300">Smember giảm thêm đến 308.000đ</p>
                                                <p class="installment text-gray-600 dark:text-gray-300">Trả góp 0% không phí chuyển đổi</p>
                                                <div class="footer flex justify-between items-center mt-2">
                                                    <div class="stars text-yellow-400">⭐️⭐️⭐️⭐️⭐️</div>
                                                    <button class="add-to-cart-btn" data-masp="${product.masp}">🛒</button>
                                                </div>
                                            </div>
                                        `;
                                    });
                                    html += `</div>`;
                                } else {
                                    html += `<p class="text-gray-600 dark:text-gray-300">Không có sản phẩm trong danh mục này.</p>`;
                                }
                                productContainer.insertAdjacentHTML('beforeend', html);

                                // Áp dụng animation
                                const newCards = document.querySelectorAll('.product-card-enter');
                                setTimeout(() => {
                                    newCards.forEach(card => {
                                        card.classList.remove('product-card-enter');
                                        card.classList.add('product-card-enter-active');
                                    });
                                }, 100);

                                // Gắn sự kiện cho các nút thêm vào giỏ hàng mới
                                attachAddToCartEvents();
                            });

                            offset += limit;
                            if (offset >= totalLoai) {
                                loadMoreButton.style.display = 'none';
                            }
                        } else {
                            loadMoreButton.style.display = 'none';
                        }
                    })
                    .catch(error => console.error('Error:', error));
            });
        }

        // Hàm gắn sự kiện cho nút thêm vào giỏ hàng
        function attachAddToCartEvents() {
            const addToCartButtons = document.querySelectorAll('.add-to-cart-btn');
            addToCartButtons.forEach(button => {
                // Xóa sự kiện cũ để tránh trùng lặp
                button.removeEventListener('click', handleAddToCart);
                button.addEventListener('click', handleAddToCart);
            });
        }

        // Xử lý sự kiện thêm vào giỏ hàng
        function handleAddToCart(event) {
            event.preventDefault();
            const button = event.target;
            const masp = button.dataset.masp;

            button.classList.add('clicked');
            setTimeout(() => {
                button.classList.remove('clicked');
            }, 500);

            fetch(`/Project1_Product/cart/add_to_cart.php?masp=${masp}&ajax=1`, {
                    method: 'GET',
                    credentials: 'same-origin'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast('Đã thêm sản phẩm vào giỏ hàng!');
                    } else {
                        if (data.message === 'Chưa đăng nhập') {
                            window.location.href = `/Project1_Product/customer/login.php?redirect=${encodeURIComponent(window.location.href)}`;
                        } else {
                            showToast(data.message, 'error');
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('Thêm Giỏ Hàng Thành Công', 'error');
                });
        }

        // Gắn sự kiện khi tải trang
        document.addEventListener('DOMContentLoaded', function() {
            attachAddToCartEvents();
        });
    </script>
</body>

</html>

<?php mysqli_close($connect); ?>