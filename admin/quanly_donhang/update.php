<?php
// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: /Project1_Product/admin/login.php");
    exit;
}

// Include database connection
include $_SERVER['DOCUMENT_ROOT'] . '/Project1_Product/connect.php';

// Get admin details
$admin_id = $_SESSION['admin_id'];

// Log the update action
$hanhdong = "Cập nhật đơn hàng của khách hàng";
$thoigian = date('Y-m-d H:i:s');
$sql_log = "INSERT INTO admin_log (admin_id, hanhdong, thoigian) VALUES (?, ?, ?)";
$stmt_log = $connect->prepare($sql_log);
$stmt_log->bind_param("iss", $admin_id, $hanhdong, $thoigian);
$stmt_log->execute();
$stmt_log->close();

// Get order ID
$madh = isset($_GET['madh']) ? (int)$_GET['madh'] : 0;
if ($madh <= 0) {
    header("Location: /Project1_Product/admin/quanly_donhang/quanly_donhang.php?error=InvalidOrderID");
    exit;
}

// Fetch order details
$sql = "SELECT * FROM donhang WHERE madh = ?";
$stmt = $connect->prepare($sql);
$stmt->bind_param("i", $madh);
$stmt->execute();
$result = $stmt->get_result();
$donhang = $result->fetch_assoc();
$stmt->close();

if (!$donhang) {
    header("Location: /Project1_Product/admin/quanly_donhang/quanly_donhang.php?error=OrderNotFound");
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $tennguoinhan = trim($_POST['tennguoinhan']);
    $diachi = trim($_POST['diachi']);
    $sodienthoai = trim($_POST['sodienthoai']);
    $phuongthuctt = trim($_POST['phuongthuctt']);
    $soluong = (int)$_POST['soluong'];
    $tinhtrang = trim($_POST['tinhtrang']);
    $custom_order_id = trim($_POST['custom_order_id']);

    // Basic validation
    if (empty($tennguoinhan) || empty($diachi) || empty($sodienthoai) || empty($phuongthuctt) || empty($tinhtrang) || $soluong <= 0) {
        $error = "Vui lòng điền đầy đủ thông tin hợp lệ.";
    } else {
        // Update order
        $sql_update = "UPDATE donhang SET tennguoinhan = ?, diachi = ?, sodienthoai = ?, phuongthuctt = ?, soluong = ?, tinhtrang = ?, custom_order_id = ? WHERE madh = ?";
        $stmt_update = $connect->prepare($sql_update);
        $stmt_update->bind_param("ssssisii", $tennguoinhan, $diachi, $sodienthoai, $phuongthuctt, $soluong, $tinhtrang, $custom_order_id, $madh);
        $stmt_update->execute();

        if ($stmt_update->affected_rows > 0) {
            header("Location: /Project1_Product/admin/quanly_donhang/quanly_donhang.php?success=OrderUpdated");
        } else {
            $error = "Không thể cập nhật đơn hàng. Vui lòng thử lại.";
        }
        $stmt_update->close();
    }
}

// Define payment methods and statuses
$payment_methods = ['COD', 'Chuyển khoản', 'Thẻ tín dụng']; // Adjust as per your system
$statuses = ['Chờ xử lý', 'Đang giao', 'Hoàn thành', 'Hủy']; // Adjust as per your system
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cập nhật đơn hàng</title>
    <link rel="stylesheet" href="/Project1_Product/styles/admin/quanly_donhang.css">
</head>
<body>
    <h2>Cập nhật đơn hàng #<?php echo $donhang['custom_order_id'] ? htmlspecialchars(substr($donhang['custom_order_id'], -8)) : sprintf("%08d", $donhang['madh']); ?></h2>
    
    <?php if (isset($error)): ?>
        <p style="color: red;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form action="" method="post">
        <label for="custom_order_id">Mã Hiển Thị:</label>
        <input type="text" name="custom_order_id" value="<?= htmlspecialchars($donhang['custom_order_id'] ?? '') ?>" placeholder="Nhập mã hiển thị (tùy chọn)"><br><br>

        <label for="tennguoinhan">Tên Người Nhận:</label>
        <input type="text" name="tennguoinhan" value="<?= htmlspecialchars($donhang['tennguoinhan'] ?? '') ?>" required><br><br>

        <label for="diachi">Địa chỉ:</label>
        <input type="text" name="diachi" value="<?= htmlspecialchars($donhang['diachi'] ?? '') ?>" required><br><br>

        <label for="sodienthoai">Số điện thoại:</label>
        <input type="tel" name="sodienthoai" value="<?= htmlspecialchars($donhang['sodienthoai'] ?? '') ?>" pattern="[0-9]{10,11}" required><br><br>

        <label for="phuongthuctt">Phương thức thanh toán:</label>
        <select name="phuongthuctt" required>
            <option value="">Chọn phương thức</option>
            <?php foreach ($payment_methods as $method): ?>
                <option value="<?= htmlspecialchars($method) ?>" <?= ($donhang['phuongthuctt'] == $method) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($method) ?>
                </option>
            <?php endforeach; ?>
        </select><br><br>

        <label for="soluong">Số lượng:</label>
        <input type="number" name="soluong" value="<?= htmlspecialchars($donhang['soluong'] ?? 1) ?>" min="1" required><br><br>

        <label for="tinhtrang">Tình trạng:</label>
        <select name="tinhtrang" required>
            <option value="">Chọn tình trạng</option>
            <?php foreach ($statuses as $status): ?>
                <option value="<?= htmlspecialchars($status) ?>" <?= ($donhang['tinhtrang'] == $status) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($status) ?>
                </option>
            <?php endforeach; ?>
        </select><br><br>

        <button type="submit">Cập nhật</button>
        <a href="/Project1_Product/admin/quanly_donhang/quanly_donhang.php">Quay lại</a>
    </form>
</body>
</html>

<?php
$connect->close();
?>