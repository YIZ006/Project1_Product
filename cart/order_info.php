<?php
session_start();
include $_SERVER['DOCUMENT_ROOT'] . '/Project1_Product/connect.php';

if (!isset($_SESSION['makh'])) {
    $_SESSION['error'] = "Vui lòng đăng nhập để đặt hàng.";
    header("Location: /Project1_Product/customer/login.php");
    exit;
}
$makh = (int)$_SESSION['makh'];

// Lấy thông tin giỏ hàng
$stmt = $connect->prepare("
    SELECT sp.masp, sp.tensp, sp.giaban, ct.soluong, sp.giaban * ct.soluong AS thanhtien
    FROM giohang gh
    JOIN chitietgiohang ct ON gh.magh = ct.magh
    JOIN sanpham sp ON ct.masp = sp.masp
    WHERE gh.makh = ? AND gh.hieuluc = 1
");
$stmt->bind_param("i", $makh);
$stmt->execute();
$result = $stmt->get_result();

$total = 0;
$cart_items = [];
while ($row = $result->fetch_assoc()) {
    $total += $row['thanhtien'];
    $cart_items[] = $row;
}
$stmt->close();

if (empty($cart_items)) {
    $_SESSION['error'] = "Giỏ hàng của bạn đang trống.";
    header("Location: /Project1_Product/cart/cart.php");
    exit;
}

// Lấy thông tin khách hàng để điền sẵn
$stmt_user = $connect->prepare("SELECT hoten, diachi, dienthoai FROM khachhang WHERE makh = ?");
$stmt_user->bind_param("i", $makh);
$stmt_user->execute();
$result_user = $stmt_user->get_result();
$user = $result_user->fetch_assoc();
$stmt_user->close();

// Xử lý form thông tin đặt hàng
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $receiver_name = trim($_POST['receiver_name']);
    $address = trim($_POST['address']);
    $phone = trim($_POST['phone']);

    if (empty($receiver_name) || empty($address) || empty($phone)) {
        $_SESSION['error'] = "Vui lòng điền đầy đủ thông tin giao hàng.";
    } elseif (!preg_match("/^[0-9]{10,11}$/", $phone)) {
        $_SESSION['error'] = "Số điện thoại không hợp lệ.";
    } else {
        // Lưu thông tin giao hàng vào session
        $_SESSION['order_info'] = [
            'receiver_name' => $receiver_name,
            'address' => $address,
            'phone' => $phone,
            'cart_items' => $cart_items,
            'total' => $total
        ];
        header("Location: /Project1_Product/cart/payment.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thông tin đặt hàng</title>
    <link rel="stylesheet" href="/Project1_Product/styles/header_index.css">
    <link rel="stylesheet" href="/Project1_Product/styles/cart.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .order-info-form {
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .order-info-form h3 {
            margin-bottom: 15px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
        }
        .form-group input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .order-summary {
            margin-top: 20px;
        }
        .order-actions {
            margin-top: 20px;
            text-align: right;
        }
        .order-actions button {
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .order-actions button:hover {
            background-color: #45a049;
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
                        <li><a href="/Project1_Product/customer/profile.php">Trang cá nhân</a></li>
                        <li><a href="/Project1_Product/cart/cart.php">Giỏ hàng</a></li>
                    </ul>
                </li>
                <li class="dropdown">
                    <a href="/Project1_Product/cart/cart.php"><img src="https://cdn-icons-png.flaticon.com/512/107/107831.png" width="24" alt="Giỏ hàng" /></a>
                </li>
                <li class="dropdown">
                    <a href="#" class="toggle-dropdown"><i class="fas fa-user-circle user-icon"></i> <?php echo htmlspecialchars($user['hoten'] ?? 'Khách hàng'); ?></a>
                    <ul class="dropdown-content">
                        <li><a href="/Project1_Product/customer/profile_customer.php">Tài Khoản</a></li>
                        <li><a href="/Project1_Product/index.php?logout=1">Đăng Xuất</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>

    <h2>Thông tin đặt hàng</h2>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <div class="order-info-form">
        <h3>Thông tin giao hàng</h3>
        <form method="post">
            <div class="form-group">
                <label for="receiver_name">Tên người nhận</label>
                <input type="text" id="receiver_name" name="receiver_name" value="<?php echo htmlspecialchars($user['hoten'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label for="address">Địa chỉ giao hàng</label>
                <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($user['diachi'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label for="phone">Số điện thoại</label>
                <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($user['dienthoai'] ?? ''); ?>" required>
            </div>

            <div class="order-summary">
                <h3>Tóm tắt đơn hàng</h3>
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
                        <?php foreach ($cart_items as $item): ?>
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
                    Tổng tiền: <span><?php echo number_format($total, 0, ',', '.'); ?> VNĐ</span>
                </div>
            </div>

            <div class="order-actions">
                <button type="submit"><i class="fas fa-check"></i> Xác nhận đặt hàng</button>
            </div>
        </form>
    </div>

    <a href="/Project1_Product/cart/cart.php" class="continue-shopping"><i class="fas fa-arrow-left"></i> Quay lại giỏ hàng</a>

    <script src="/Project1_Product/js/header.js"></script>
</body>
</html>
<?php include $_SERVER['DOCUMENT_ROOT'] . '/Project1_Product/footer.php'; ?>