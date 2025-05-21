<?php
session_start();
include $_SERVER['DOCUMENT_ROOT'] . '/Project1_Product/connect.php';

if (!isset($_SESSION['order_completed'])) {
    $_SESSION['error'] = "Không tìm thấy thông tin đơn hàng.";
    header("Location: /Project1_Product/cart/cart.php");
    exit;
}
$order = $_SESSION['order_completed'];
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đặt hàng thành công</title>
    <link rel="stylesheet" href="/Project1_Product/styles/header_index.css">
    <link rel="stylesheet" href="/Project1_Product/styles/cart.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .order-complete {
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            text-align: center;
        }
        .order-complete h3 {
            color: #4CAF50;
        }
        .order-details {
            margin-top: 20px;
            text-align: left;
        }
        .order-details h4 {
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="trangchu">
        <div class="menu">
            <a href="/Project1_Product/index.php">
                <div class="logo"><img src="https://png.pngtree.com/png-clipart/20230207/original/pngtree-beauty-logo-design-png-image_8947095.png" alt="Megatech Logo"></div>
            </a>
            <div class="box">
                <span class="icon"><i class="fa fa-search"></i></span>
                <input type="search" id="search" placeholder="Tìm kiếm sản phẩm..." />
            </div>
            <ul class="menu-list">
                <li class="dropdown">
                    <a href="#"><img src="https://cdn-icons-png.flaticon.com/512/1828/1828859.png" width="24" alt="Danh mục" /></a>
                    <ul class="dropdown-content">
                        <li><a href="/Project1_Product/index.php">Trang chủ</a></li>
                        <li><a href="/Project1_Product/customer/profile_customer.php">Trang cá nhân</a></li>
                        <li><a href="/Project1_Product/cart/cart.php">Giỏ hàng</a></li>
                    </ul>
                </li>
                <li class="dropdown">
                    <a href="/Project1_Product/cart/cart.php"><img src="https://cdn-icons-png.flaticon.com/512/107/107831.png" width="24" alt="Giỏ hàng" /></a>
                </li>
                <li class="dropdown">
                    <a href="#" class="toggle-dropdown"><i class="fas fa-user-circle user-icon"></i> <?php echo htmlspecialchars($_SESSION['hoten']); ?></a>
                    <ul class="dropdown-content">
                        <li><a href="/Project1_Product/customer/profile_customer.php">Tài Khoản</a></li>
                        <li><a href="/Project1_Product/index.php?logout=1">Đăng Xuất</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>

    <!-- Phần còn lại của file giữ nguyên -->
    <div class="order-complete">
        <h3>Đặt hàng thành công!</h3>
        <p>Cảm ơn bạn đã mua sắm tại Megatech. Đơn hàng của bạn đã được ghi nhận.</p>

        <div class="order-details">
            <h4>Thông tin đơn hàng</h4>
            <p><strong>Mã đơn hàng:</strong> <?php echo htmlspecialchars($order['custom_order_id']); ?></p>
            <p><strong>Phương thức thanh toán:</strong> 
                <?php echo $order['payment_method'] == 'COD' ? 'Thanh toán khi nhận hàng (COD)' : 'Chuyển khoản ngân hàng'; ?>
            </p>
            <p><strong>Tên người nhận:</strong> <?php echo htmlspecialchars($order['receiver_name']); ?></p>
            <p><strong>Địa chỉ:</strong> <?php echo htmlspecialchars($order['address']); ?></p>
            <p><strong>Số điện thoại:</strong> <?php echo htmlspecialchars($order['phone']); ?></p>

            <h4>Chi tiết đơn hàng</h4>
            <table class="cart-table">
                <thead>
                    <tr>
                        <th>Mã sản phẩm</th>
                        <th>Tên sản phẩm</th>
                        <th>Giá bán</th>
                        <th>Số lượng</th>
                        <th>Thành tiền</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($order['cart_items'] as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['masp']); ?></td>
                            <td><?php echo htmlspecialchars($item['tensp']); ?></td>
                            <td><?php echo number_format($item['giaban'], 0, ',', '.'); ?> VNĐ</td>
                            <td><?php echo $item['soluong']; ?></td>
                            <td><?php echo number_format($item['thanhtien'], 0, ',', '.'); ?> VNĐ</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="total-amount">
                Tổng tiền: <span><?php echo number_format($order['total'], 0, ',', '.'); ?> VNĐ</span>
            </div>
        </div>
    </div>

    <a href="/Project1_Product/index.php" class="continue-shopping"><i class="fas fa-shopping-bag"></i> Tiếp tục mua sắm</a>

    <script src="/Project1_Product/js/header.js"></script>
</body>
</html>
<?php include $_SERVER['DOCUMENT_ROOT'] . '/Project1_Product/footer.php'; ?>