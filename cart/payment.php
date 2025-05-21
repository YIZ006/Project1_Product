<?php
session_start();
include $_SERVER['DOCUMENT_ROOT'] . '/Project1_Product/connect.php';

// Kiểm tra thông tin đặt hàng
if (!isset($_SESSION['makh']) || !isset($_SESSION['order_info'])) {
    $_SESSION['error'] = "Không tìm thấy thông tin đặt hàng.";
    header("Location: /Project1_Product/cart/cart.php");
    exit;
}

$makh = (int)$_SESSION['makh'];
$order_info = $_SESSION['order_info'];
$cart_items = $order_info['cart_items'];
$total = $order_info['total'];

// Khởi tạo biến display_order_id
$display_order_id = '';

// Xử lý thanh toán
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $payment_method = isset($_POST['payment_method']) ? $_POST['payment_method'] : '';
    if (!in_array($payment_method, ['COD', 'VNPay'])) { // Updated to include VNPay
        $_SESSION['error'] = "Phương thức thanh toán không hợp lệ.";
        header("Location: /Project1_Product/cart/payment.php");
        exit;
    }

    // Tạo custom_order_id và đơn hàng
    $custom_order_id = $makh . date('YmdHis');
    $display_order_id = substr($custom_order_id, -8);

    // Kiểm tra xem custom_order_id đã tồn tại chưa
    $stmt_check = $connect->prepare("SELECT custom_order_id FROM donhang WHERE custom_order_id = ?");
    $stmt_check->bind_param("s", $custom_order_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    if ($result_check->num_rows > 0) {
        sleep(1);
        $custom_order_id = $makh . date('YmdHis');
        $display_order_id = substr($custom_order_id, -8);
    }
    $stmt_check->close();

    // Tạo bản ghi đơn hàng
    $stmt_donhang = $connect->prepare("
        INSERT INTO donhang (makh, ngaytao, ngaydat, tinhtrang, soluong, tongtien, custom_order_id, tennguoinhan, diachi, sodienthoai, phuongthuctt)
        VALUES (?, CURDATE(), CURDATE(), 'Chờ thanh toán', ?, ?, ?, ?, ?, ?, ?)
    ");
    $soluong = array_sum(array_column($cart_items, 'soluong'));
    $tennguoinhan = $order_info['receiver_name'];
    $diachi = $order_info['address'];
    $sodienthoai = $order_info['phone'];
    $stmt_donhang->bind_param("iiiissis", $makh, $soluong, $total, $custom_order_id, $tennguoinhan, $diachi, $sodienthoai, $payment_method);
    $stmt_donhang->execute();
    $madh = $connect->insert_id;
    $stmt_donhang->close();

    // If VNPay, skip additional payment info (handled in vnpay_create_payment.php)
    if ($payment_method === 'VNPay') {
        // Store order ID for VNPay processing
        $_SESSION['vnpay_order_id'] = $madh;
        $_SESSION['vnpay_custom_order_id'] = $custom_order_id;
        // No need to insert into bank_payment_info
    }

    // Cập nhật trạng thái đơn hàng thành "Chờ xử lý" và thêm chi tiết
    $stmt_update_dh = $connect->prepare("UPDATE donhang SET tinhtrang = 'Chờ xử lý' WHERE madh = ?");
    $stmt_update_dh->bind_param("i", $madh);
    $stmt_update_dh->execute();
    $stmt_update_dh->close();

    $stmt_chitiet = $connect->prepare("INSERT INTO chitietdonhang (madh, masp, soluong) VALUES (?, ?, ?)");
    foreach ($cart_items as $item) {
        $stmt_chitiet->bind_param("iii", $madh, $item['masp'], $item['soluong']);
        $stmt_chitiet->execute();

        // Cập nhật số lượng tồn kho
        $stmt_update_stock = $connect->prepare("UPDATE sanpham SET soluongton = soluongton - ? WHERE masp = ?");
        $stmt_update_stock->bind_param("ii", $item['soluong'], $item['masp']);
        $stmt_update_stock->execute();
        $stmt_update_stock->close();
    }
    $stmt_chitiet->close();

    // Lấy magh
    $stmt = $connect->prepare("SELECT magh FROM giohang WHERE makh = ? AND hieuluc = 1");
    $stmt->bind_param("i", $makh);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $magh = $row['magh'];
    $stmt->close();

    // Vô hiệu hóa giỏ hàng
    $stmt_update_gh = $connect->prepare("UPDATE giohang SET hieuluc = 0, tongsl = 0, tongtien = 0 WHERE magh = ?");
    $stmt_update_gh->bind_param("i", $magh);
    $stmt_update_gh->execute();
    $stmt_update_gh->close();

    // Tạo giỏ hàng mới
    $stmt_taogh = $connect->prepare("INSERT INTO giohang (makh, ngaytao, hieuluc) VALUES (?, CURDATE(), 1)");
    $stmt_taogh->bind_param("i", $makh);
    $stmt_taogh->execute();
    $stmt_taogh->close();

    // Lưu thông tin đơn hàng vào session để hiển thị ở trang hoàn tất
    $_SESSION['order_completed'] = [
        'madh' => $madh,
        'custom_order_id' => $display_order_id,
        'payment_method' => $payment_method,
        'receiver_name' => $order_info['receiver_name'],
        'address' => $order_info['address'],
        'phone' => $order_info['phone'],
        'cart_items' => $cart_items,
        'total' => $total
    ];

    // Xóa thông tin đặt hàng tạm thời
    unset($_SESSION['order_info']);

    // If VNPay, redirect to VNPay processing page
    if ($payment_method === 'VNPay') {
        header("Location: /Project1_Product/vnpay/vnpay_create_payment.php?amount=$total&order_id=$madh");
        exit;
    }

    header("Location: /Project1_Product/cart/complete.php");
    exit;
}
?>`

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh toán</title>
    <link rel="stylesheet" href="/Project1_Product/styles/header_index.css">
    <link rel="stylesheet" href="/Project1_Product/styles/cart.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .payment-form {
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .payment-form h3 {
            margin-bottom: 15px;
        }

        .info-group {
            margin-bottom: 15px;
        }

        .info-group label {
            font-weight: bold;
        }

        .payment-method {
            margin-bottom: 15px;
        }

        .payment-method label {
            display: block;
            margin-bottom: 5px;
        }

        .payment-actions {
            margin-top: 20px;
            text-align: right;
        }

        .payment-actions button {
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .payment-actions button:hover {
            background-color: #45a049;
        }

        .qr-code {
            display: none;
            text-align: center;
            margin-top: 15px;
        }

        .qr-code img {
            max-width: 200px;
        }

        .bank-info {
            margin-top: 10px;
            text-align: left;
        }

        .bank-info p {
            margin: 5px 0;
        }

        .timer {
            display: none;
            color: red;
            font-weight: bold;
            margin-top: 10px;
        }

        .vnpay-form button {
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 10px;
        }

        .vnpay-form button:hover {
            background-color: #0056b3;
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
    <h2>Thanh toán</h2>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="error"><?php echo htmlspecialchars($_SESSION['error']);
                            unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <div class="payment-form">
        <h3>Thông tin giao hàng</h3>
        <div class="info-group">
            <label>Tên người nhận:</label> <?php echo htmlspecialchars($order_info['receiver_name']); ?>
        </div>
        <div class="info-group">
            <label>Địa chỉ:</label> <?php echo htmlspecialchars($order_info['address']); ?>
        </div>
        <div class="info-group">
            <label>Số điện thoại:</label> <?php echo htmlspecialchars($order_info['phone']); ?>
        </div>

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

        <form method="post" id="paymentForm">
            <div class="payment-method">
                <h3>Phương thức thanh toán</h3>
                <label><input type="radio" name="payment_method" value="COD" checked> Thanh toán khi nhận hàng (COD)</label>
                <label><input type="radio" name="payment_method" value="VNPay" onclick="togglePaymentForm()"> Thanh toán VNPay</label>

                <div class="vnpay-form" id="vnpayForm" style="display: none;">
                    <form action="/Project1_Product/vnpay/vnpay_create_payment.php" method="POST">
                        <input type="hidden" name="amount" value="<?php echo $total; ?>">
                        <input type="hidden" name="order_id" value="<?php echo htmlspecialchars($display_order_id); ?>">
                        <button type="submit">Thanh toán VNPay</button>
                    </form>
                </div>
            </div>

            <div class="payment-actions">
                <button type="submit" id="confirmPayment"><i class="fas fa-credit-card"></i> Xác nhận thanh toán</button>
            </div>
        </form>
    </div>

    <a href="/Project1_Product/cart/order_info.php" class="continue-shopping"><i class="fas fa-arrow-left"></i> Quay lại thông tin đặt hàng</a>

    <script>
        function togglePaymentForm() {
            const vnpayForm = document.getElementById('vnpayForm');
            const confirmButton = document.getElementById('confirmPayment');
            const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;

            if (paymentMethod === 'VNPay') {
                vnpayForm.style.display = 'block';
                confirmButton.style.display = 'none';
            } else {
                vnpayForm.style.display = 'none';
                confirmButton.style.display = 'block';
            }
        }

        document.querySelector('.payment-actions button').addEventListener('click', function(e) {
            this.disabled = true;
            this.form.submit();
        });
    </script>

    <script src="/Project1_Product/js/header.js"></script>
</body>

</html>
<?php include $_SERVER['DOCUMENT_ROOT'] . '/Project1_Product/footer.php'; ?>
<script>
    document.querySelector('.payment-actions button').addEventListener('click', function(e) {
        this.disabled = true;
        this.form.submit();
    });
</script>