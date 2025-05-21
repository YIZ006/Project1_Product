<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include $_SERVER['DOCUMENT_ROOT'] . '/Project1_Product/connect.php';


// Kiểm tra kết nối procedural
if (mysqli_connect_errno()) {
    die("Kết nối cơ sở dữ liệu thất bại: " . mysqli_connect_error());
}

if (!isset($_GET['masp']) || !is_numeric($_GET['masp'])) {
    $_SESSION['error'] = "Mã sản phẩm không hợp lệ.";
    header("Location: /Project1_Product/index.php");
    exit;
}

$masp = (int)$_GET['masp'];

// ----- Bắt đầu phần sửa procedural -----
// Chuẩn bị câu truy vấn
$sql = "SELECT masp, tensp, giaban, mota, hinhanh FROM sanpham WHERE masp = ?";
$stmt = mysqli_prepare($connect, $sql);
if (!$stmt) {
    $_SESSION['error'] = "Lỗi truy vấn cơ sở dữ liệu.";
    header("Location: /Project1_Product/index.php");
    exit;
}

// bind tham số và thực thi
mysqli_stmt_bind_param($stmt, "i", $masp);
mysqli_stmt_execute($stmt);

// Lấy kết quả
$result = mysqli_stmt_get_result($stmt);
if (!$result) {
    $_SESSION['error'] = "Lỗi khi lấy dữ liệu sản phẩm.";
    header("Location: /Project1_Product/index.php");
    exit;
}

if (mysqli_num_rows($result) === 0) {
    $_SESSION['error'] = "Sản phẩm không tồn tại.";
    header("Location: /Project1_Product/index.php");
    exit;
}

$product = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);
// ----- Kết thúc phần sửa procedural -----
?>

<?php include $_SERVER['DOCUMENT_ROOT'] . '/Project1_Product/header.php';?>
<body>
    <div class="container">
        <h2>Thông Tin Chi Tiết Sản Phẩm</h2>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <div class="product-layout">
            <!-- Left Column: Image and Technical Specifications -->
            <div class="product-left">
                <?php if (!empty($product['hinhanh'])): ?>
                    <div class="product-image">
                        <img src="/Project1_Product/uploads/<?= htmlspecialchars($product['hinhanh']) ?>" alt="<?= htmlspecialchars($product['tensp']) ?>">
                    </div>
                <?php else: ?>
                    <p>Không có hình ảnh sản phẩm.</p>
                <?php endif; ?>
                <h3>Thông số kỹ thuật</h3>
                <ul class="specs-list">
                    <li><strong>Màn hình:</strong> 27 inch</li>
                    <li><strong>Độ phân giải:</strong> UHD (3840 x 2160)</li>
                    <li><strong>Tần số quét:</strong> 60Hz</li>
                    <li><strong>Loại màn hình:</strong> IPS</li>
                    <li><strong>Cổng kết nối:</strong> HDMI, DisplayPort, USB Type-C</li>
                    <li><strong>Độ sáng:</strong> 350 cd/m²</li>
                </ul>
            </div>

            <!-- Right Column: Product Details -->
            <div class="product-right">
                <h3><?php echo htmlspecialchars($product['tensp']); ?></h3>
                <p><strong>Giá bán:</strong> <?php echo number_format($product['giaban'], 0, ',', '.'); ?> VNĐ</p>
                <p><strong>Mô tả:</strong> <?php echo nl2br(htmlspecialchars($product['mota'])); ?></p>
                <p><strong>Mã sản phẩm:</strong> <?php echo htmlspecialchars($product['masp']); ?></p>
                <p><strong>Bảo hành:</strong> 24 tháng</p>
                <p><strong>Khuyến mãi:</strong> Giảm thêm 308.000đ cho thành viên</p>
                <form method="post" action="/Project1_Product/cart/add_to_cart.php">
                    <input type="hidden" name="masp" value="<?php echo $product['masp']; ?>">
                    <label for="quantity">Số lượng:</label>
                    <input type="number" id="quantity" name="quantity" value="1" min="1" style="width: 60px; padding: 5px;">
                    <button type="submit" name="add_to_cart">Thêm vào giỏ hàng</button>
                </form>
            </div>
        </div>
        <a href="/Project1_Product/index.php" class="back-link">Quay lại danh sách sản phẩm</a>
    </div>
</body>

<?php include $_SERVER['DOCUMENT_ROOT'] . '/Project1_Product/footer.php'; ?>
<style>
    .sidebar-left, .main-content, .sidebar, .main-menu, .product-category {
        display: none !important;
    }
    .banner-container, .slider, .carousel, .main-slider {
        display: none !important;
    }
    body {
        color: #fff; /* Set default text color to white */
        font-family: Arial, sans-serif;
    }
    .container {
        max-width: 1200px;
        margin: auto;
        padding: 20px;
        background-color: #2a2a2a;
        border-radius: 5px;
    }

    h2 {
        color: #fff;
        margin-bottom: 20px;
    }

    .success {
        color: #00cc00;
        margin-bottom: 10px;
    }

    .error {
        color: #ff3333;
        margin-bottom: 10px;
    }

    .product-layout {
        display: flex;
        gap: 20px;
    }

    .product-left, .product-right {
        flex: 1;
    }

    .product-image {
        margin-bottom: 20px;
        text-align: center;
    }

    .product-image img {
        max-width: 100%;
        height: auto;
        border: 2px solid #fff;
        border-radius: 5px;
    }

    .product-left h3,
    .product-right h3 {
        color: #fff;
        margin-bottom: 10px;
    }

    .product-left p,
    .product-right p {
        margin: 5px 0;
        color: #fff;
    }

    .product-left strong,
    .product-right strong {
        color: #fff;
    }

    .specs-list {
        list-style-type: none;
        padding: 0;
    }

    .specs-list li {
        margin: 5px 0;
    }

    .product-right input[type="number"] {
        width: 60px;
        padding: 5px;
        margin-right: 10px;
        background-color: #333;
        color: #fff;
        border: 1px solid #555;
        border-radius: 3px;
    }

    .product-right button {
        background-color: #003087;
        color: #fff;
        border: none;
        padding: 8px 15px;
        cursor: pointer;
        border-radius: 3px;
    }