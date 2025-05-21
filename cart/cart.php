<?php
session_start();
include $_SERVER['DOCUMENT_ROOT'] . '/Project1_Product/connect.php';

if (!isset($_SESSION['makh'])) {
    $_SESSION['error'] = "Vui lòng đăng nhập để xem giỏ hàng.";
    header("Location: /Project1_Product/customer/login.php");
    exit;
}
$makh = (int)$_SESSION['makh'];

// Xử lý cập nhật số lượng
if (isset($_POST['update_quantity']) && isset($_POST['masp']) && isset($_POST['new_quantity'])) {
    $masp = (int)$_POST['masp'];
    $new_quantity = (int)$_POST['new_quantity'];

    try {
        if ($new_quantity < 1) {
            $_SESSION['error'] = "Số lượng phải lớn hơn 0.";
        } else {
            $stmt_stock = $connect->prepare("SELECT soluongton FROM sanpham WHERE masp = ?");
            $stmt_stock->bind_param("i", $masp);
            $stmt_stock->execute();
            $result_stock = $stmt_stock->get_result();
            $row_stock = $result_stock->fetch_assoc();
            $stmt_stock->close();

            if ($new_quantity > $row_stock['soluongton']) {
                $_SESSION['error'] = "Số lượng vượt quá tồn kho. Chỉ còn " . $row_stock['soluongton'] . " sản phẩm.";
            } else {
                $stmt = $connect->prepare("SELECT magh FROM giohang WHERE makh = ? AND hieuluc = 1");
                $stmt->bind_param("i", $makh);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows > 0) {
                    $row = $result->fetch_assoc();
                    $magh = $row['magh'];

                    $stmt_update = $connect->prepare("UPDATE chitietgiohang SET soluong = ? WHERE magh = ? AND masp = ?");
                    $stmt_update->bind_param("iii", $new_quantity, $magh, $masp);
                    $stmt_update->execute();
                    $stmt_update->close();

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

                    $_SESSION['success'] = "Cập nhật số lượng thành công.";
                }
            }
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Có lỗi xảy ra: " . $e->getMessage();
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Xử lý xóa sản phẩm
if (isset($_POST['delete_item']) && isset($_POST['masp'])) {
    $masp = (int)$_POST['masp'];
    try {
        $stmt = $connect->prepare("SELECT magh FROM giohang WHERE makh = ? AND hieuluc = 1");
        $stmt->bind_param("i", $makh);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $magh = $row['magh'];

            $stmt_delete = $connect->prepare("DELETE FROM chitietgiohang WHERE magh = ? AND masp = ?");
            $stmt_delete->bind_param("ii", $magh, $masp);
            $stmt_delete->execute();
            $stmt_delete->close();

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

            $_SESSION['success'] = "Đã xóa sản phẩm khỏi giỏ hàng.";
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Có lỗi xảy ra: " . $e->getMessage();
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Xử lý xóa toàn bộ giỏ hàng
if (isset($_POST['delete_all'])) {
    try {
        $stmt = $connect->prepare("SELECT magh FROM giohang WHERE makh = ? AND hieuluc = 1");
        $stmt->bind_param("i", $makh);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $magh = $row['magh'];

            $stmt_delete = $connect->prepare("DELETE FROM chitietgiohang WHERE magh = ?");
            $stmt_delete->bind_param("i", $magh);
            $stmt_delete->execute();
            $stmt_delete->close();

            $stmt_update_gh = $connect->prepare("UPDATE giohang SET tongsl = 0, tongtien = 0 WHERE magh = ?");
            $stmt_update_gh->bind_param("i", $magh);
            $stmt_update_gh->execute();
            $stmt_update_gh->close();

            $_SESSION['success'] = "Đã xóa toàn bộ giỏ hàng.";
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Có lỗi xảy ra: " . $e->getMessage();
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

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

// Lấy thông tin khách hàng để hiển thị tên
$stmt_user = $connect->prepare("SELECT hoten FROM khachhang WHERE makh = ?");
$stmt_user->bind_param("i", $makh);
$stmt_user->execute();
$result_user = $stmt_user->get_result();
$user = $result_user->fetch_assoc();
$display_name = $user['hoten'] ?? 'Khách hàng';
$stmt_user->close();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giỏ hàng</title>
    <link rel="stylesheet" href="/Project1_Product/styles/global.css">
    <link rel="stylesheet" href="/Project1_Product/styles/header_index.css">
    <link rel="stylesheet" href="/Project1_Product/styles/cart.css">
    <link rel="stylesheet" href="/Project1_Product/styles/footer_index.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
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
                    <a href="#" class="toggle-dropdown"><i class="fas fa-user-circle user-icon"></i> <?php echo htmlspecialchars($display_name); ?></a>
                    <ul class="dropdown-content">
                        <li><a href="/Project1_Product/customer/profile_customer.php">Tài Khoản</a></li>
                        <li><a href="/Project1_Product/index.php?logout=1">Đăng Xuất</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>

    <h2>Giỏ hàng của bạn</h2>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <?php if (!empty($cart_items)): ?>
        <table class="cart-table">
            <thead>
                <tr>
                    <th>Mã sản phẩm</th>
                    <th>Tên sản phẩm</th>
                    <th>Giá bán</th>
                    <th>Số lượng</th>
                    <th>Thành tiền</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cart_items as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['masp']); ?></td>
                        <td><?php echo htmlspecialchars($item['tensp']); ?></td>
                        <td><?php echo number_format($item['giaban'], 0, ',', '.'); ?> VNĐ</td>
                        <td>
                            <form method="post" style="display: inline;">
                                <input type="hidden" name="masp" value="<?php echo $item['masp']; ?>">
                                <input type="number" name="new_quantity" value="<?php echo $item['soluong']; ?>" min="1" style="width: 60px; padding: 5px;">
                                <button type="submit" name="update_quantity" style="padding: 5px 10px; margin-left: 5px;">Cập nhật</button>
                            </form>
                        </td>
                        <td><?php echo number_format($item['thanhtien'], 0, ',', '.'); ?> VNĐ</td>
                        <td>
                            <form method="post" style="display: inline;">
                                <input type="hidden" name="masp" value="<?php echo $item['masp']; ?>">
                                <button type="submit" name="delete_item" style="padding: 5px 10px; color: red;"><i class="fas fa-trash-alt"></i> Xóa</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="total-amount">
            Tổng tiền: <span><?php echo number_format($total, 0, ',', '.'); ?> VNĐ</span>
        </div>

        <div class="cart-actions">
            <form method="post">
                <button type="submit" name="delete_all" class="delete-all"><i class="fas fa-trash-alt"></i> Xóa giỏ hàng</button>
            </form>
            <a href="/Project1_Product/cart/order_info.php" class="checkout"><i class="fas fa-shopping-cart"></i> Tiến hành đặt hàng</a>
        </div>
    <?php else: ?>
        <p>Giỏ hàng của bạn đang trống.</p>
    <?php endif; ?>

    <a href="/Project1_Product/index.php" class="continue-shopping"><i class="fas fa-shopping-bag"></i> Tiếp tục mua sắm</a>

    <?php include $_SERVER['DOCUMENT_ROOT'] . '/Project1_Product/footer.php'; ?>

    <script src="/Project1_Product/js/header.js"></script>
</body>
</html>