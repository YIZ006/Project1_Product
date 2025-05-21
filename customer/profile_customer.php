<?php
ob_start();
session_start();
include $_SERVER['DOCUMENT_ROOT'] . '/Project1_Product/connect.php';

// Kiểm tra kết nối cơ sở dữ liệu
if ($connect->connect_error) {
    die("Kết nối thất bại: " . $connect->connect_error);
}

// Kiểm tra đăng nhập
if (!isset($_SESSION['makh'])) {
    header("Location: /Project1_Product/index.php");
    exit();
}

// Xác định tab đang chọn
$tab = $_GET['view'] ?? 'order';
$makh = $_SESSION['makh'];
$success_message = '';
$error_message = '';

// Xử lý hủy đơn hàng
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_order'])) {
    $madh = (int)$_POST['madh'];
    $custom_order_id = $_POST['custom_order_id'];
    $lydo_huy = trim($_POST['lydo_huy']);
    $thoigian_huy = date('Y-m-d H:i:s');

    if (empty($lydo_huy)) {
        $error_message = "Vui lòng chọn lý do hủy đơn hàng!";
    } else {
        // Kiểm tra trạng thái đơn hàng
        $stmt_check = $connect->prepare("SELECT tinhtrang FROM donhang WHERE madh = ? AND makh = ?");
        $stmt_check->bind_param("ii", $madh, $makh);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        $donhang = $result_check->fetch_assoc();
        $stmt_check->close();

        if ($donhang && in_array($donhang['tinhtrang'], ['Chờ thanh toán', 'Chờ xử lý'])) {
            // Cập nhật trạng thái đơn hàng thành "Đã hủy"
            $stmt_update = $connect->prepare("UPDATE donhang SET tinhtrang = 'Đã hủy' WHERE madh = ?");
            $stmt_update->bind_param("i", $madh);
            if ($stmt_update->execute()) {
                // Phục hồi số lượng tồn kho
                $stmt_stock = $connect->prepare("
                    UPDATE sanpham sp
                    JOIN chitietdonhang ct ON sp.masp = ct.masp
                    SET sp.soluongton = sp.soluongton + ct.soluong
                    WHERE ct.madh = ?
                ");
                $stmt_stock->bind_param("i", $madh);
                $stmt_stock->execute();
                $stmt_stock->close();

                // Ghi log hủy đơn hàng
                $stmt_log = $connect->prepare("
                    INSERT INTO order_cancellation_log (madh, makh, custom_order_id, lydo_huy, thoigian_huy)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt_log->bind_param("iisss", $madh, $makh, $custom_order_id, $lydo_huy, $thoigian_huy);
                $stmt_log->execute();
                $stmt_log->close();

                $success_message = "Đơn hàng đã được hủy thành công! Kiểm tra console để xem thông báo.";
            } else {
                $error_message = "Lỗi khi hủy đơn hàng: " . $connect->error;
            }
            $stmt_update->close();
        } else {
            $error_message = "Không thể hủy đơn hàng ở trạng thái này!";
        }
    }
}

// Xử lý thay đổi mật khẩu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tab === 'password') {
    $old_pass = trim($_POST['old_pass']);
    $new_pass = trim($_POST['new_pass']);

    if (empty($old_pass) || empty($new_pass)) {
        $error_message = "Vui lòng nhập đầy đủ mật khẩu cũ và mới!";
    } elseif (strlen($new_pass) < 6) {
        $error_message = "Mật khẩu mới phải có ít nhất 6 ký tự!";
    } else {
        $stmt = $connect->prepare("SELECT matkhau FROM khachhang WHERE makh = ?");
        $stmt->bind_param("i", $makh);
        $stmt->execute();
        $result = $stmt->get_result();
        $kh = $result->fetch_assoc();
        $stmt->close();

        if (password_verify($old_pass, $kh['matkhau'])) {
            $new_pass_hashed = password_hash($new_pass, PASSWORD_DEFAULT);
            $stmt = $connect->prepare("UPDATE khachhang SET matkhau = ? WHERE makh = ?");
            $stmt->bind_param("si", $new_pass_hashed, $makh);
            if ($stmt->execute()) {
                $success_message = "Đổi mật khẩu thành công!";
                $hanhdong = "Khách hàng thay đổi mật khẩu (makh: $makh)";
                $thoigian = date('Y-m-d H:i:s');
                $stmt_log = $connect->prepare("INSERT INTO customer_log (makh, hanhdong, thoigian) VALUES (?, ?, ?)");
                $stmt_log->bind_param("iss", $makh, $hanhdong, $thoigian);
                $stmt_log->execute();
                $stmt_log->close();
            } else {
                $error_message = "Lỗi khi đổi mật khẩu: " . $connect->error;
            }
            $stmt->close();
        } else {
            $error_message = "Mật khẩu cũ không đúng!";
        }
    }
}

// Xử lý cập nhật thông tin cá nhân
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tab === 'config') {
    $hoten = trim($_POST['hoten']);
    $diachi = trim($_POST['diachi']);
    $dienthoai = trim($_POST['dienthoai']);
    $gender = trim($_POST['Gender']) ?: NULL;

    if (empty($hoten)) {
        $error_message = "Vui lòng nhập họ tên!";
    } elseif (!preg_match('/^[0-9]{10,15}$/', $dienthoai) && !empty($dienthoai)) {
        $error_message = "Số điện thoại không hợp lệ!";
    } elseif ($gender && !in_array($gender, ['Nam', 'Nữ'])) {
        $error_message = "Giới tính không hợp lệ!";
    } else {
        $stmt = $connect->prepare("UPDATE khachhang SET hoten = ?, diachi = ?, dienthoai = ?, Gender = ? WHERE makh = ?");
        $stmt->bind_param("ssssi", $hoten, $diachi, $dienthoai, $gender, $makh);
        if ($stmt->execute()) {
            $success_message = "Cập nhật thông tin thành công!";
            $_SESSION['hoten'] = $hoten;
            $hanhdong = "Khách hàng cập nhật thông tin cá nhân (makh: $makh)";
            $thoigian = date('Y-m-d H:i:s');
            $stmt_log = $connect->prepare("INSERT INTO customer_log (makh, hanhdong, thoigian) VALUES (?, ?, ?)");
            $stmt_log->bind_param("iss", $makh, $hanhdong, $thoigian);
            $stmt_log->execute();
            $stmt_log->close();
        } else {
            $error_message = "Lỗi khi cập nhật thông tin: " . $connect->error;
        }
        $stmt->close();
    }
}

// Kiểm tra trạng thái đăng nhập
$is_logged_in = isset($_SESSION['makh']);
$display_name = $is_logged_in ? $_SESSION['hoten'] : 'Đăng Nhập';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tài khoản - Megatech</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="/Project1_Product/styles/global.css">
    <link rel="stylesheet" href="/Project1_Product/styles/header_index.css">
    <link rel="stylesheet" href="/Project1_Product/styles/profile.css">
    <style>
        .cancel-form {
            display: none;
            margin-top: 10px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: #f9f9f9;
        }
        .cancel-form select {
            width: 100%;
            padding: 5px;
            margin-bottom: 10px;
        }
        .cancel-form button {
            padding: 5px 10px;
            background-color: #ff4444;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .cancel-form button:hover {
            background-color: #cc0000;
        }
        .action-links a {
            margin-right: 10px;
            color: #007bff;
            text-decoration: none;
        }
        .action-links a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="trangchu">
        <div class="menu">
            <a href="/Project1_Product/index.php">
                <div class="logo"><img src="/Project1_Product/uploads/logo_transparent.png" alt="Megatech Logo"></div>
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
                        <li><a href="/Project1_Product/customer/cart.php">Giỏ hàng</a></li>
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
                <li>
                    <label class="theme-switch">
                        <input type="checkbox">
                        <span class="slider">
                            <span class="sun">☀️</span>
                            <span class="moon">🌙</span>
                        </span>
                    </label>
                </li>
            </ul>
        </div>
    </div>

    <div class="container-account">
        <div class="menu-account">
            <h3>Tài khoản của bạn</h3>
            <a href="?view=info" class="<?php echo $tab === 'info' ? 'active' : ''; ?>"><i class="fas fa-user"></i>Thông tin cá nhân</a>
            <a href="?view=password" class="<?php echo $tab === 'password' ? 'active' : ''; ?>">Thay đổi mật khẩu</a>
            <a href="?view=order" class="<?php echo $tab === 'order' ? 'active' : ''; ?>"><i class="fas fa-box"></i>Đơn hàng đã mua</a>
            <a href="?view=saved" class="<?php echo $tab === 'saved' ? 'active' : ''; ?>"><i class="fas fa-eye"></i>Sản phẩm đang lưu</a>
            <a href="?view=config" class="<?php echo $tab === 'config' ? 'active' : ''; ?>">Cấu hình của tôi</a>
        </div>

        <div class="content-account">
            <?php if (!empty($error_message)): ?>
                <div class="error"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>
            <?php if (!empty($success_message)): ?>
                <div class="success"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>

            <?php
            switch ($tab) {
                case 'order':
                    echo "<h2>Danh sách đơn hàng</h2>";
                    // Chỉ hiển thị các đơn hàng không bị hủy
                    $stmt_dh = $connect->prepare("
                        SELECT madh, custom_order_id, makh, ngaytao, soluong, tinhtrang, ngaydat, tongtien, tennguoinhan, diachi, sodienthoai 
                        FROM donhang 
                        WHERE makh = ? AND tinhtrang != 'Đã hủy'
                        ORDER BY ngaytao DESC
                    ");
                    $stmt_dh->bind_param("i", $makh);
                    $stmt_dh->execute();
                    $result_dh = $stmt_dh->get_result();

                    if ($result_dh->num_rows > 0) {
                        while ($donhang = $result_dh->fetch_assoc()) {
                            $display_order_id = substr($donhang['custom_order_id'], -8);
                            echo "<div style='border-bottom: 1px solid #444; margin-bottom: 20px; padding-bottom: 10px'>";
                            echo "<strong>Mã đơn hàng:</strong> " . htmlspecialchars($display_order_id) . "<br>";
                            echo "<strong>Tên người nhận:</strong> " . htmlspecialchars($donhang['tennguoinhan'] ?? 'Không xác định') . "<br>";
                            echo "<strong>Địa chỉ giao hàng:</strong> " . htmlspecialchars($donhang['diachi'] ?? 'Không xác định') . "<br>";
                            echo "<strong>Số điện thoại:</strong> " . htmlspecialchars($donhang['sodienthoai'] ?? 'Không xác định') . "<br>";
                            echo "<strong>Ngày đặt:</strong> " . htmlspecialchars($donhang['ngaydat'] ?? $donhang['ngaytao']) . "<br>";
                            echo "<strong>Trạng thái:</strong> " . htmlspecialchars($donhang['tinhtrang'] ?? 'Chưa xác định') . "<br>";
                            echo "<strong>Tổng tiền:</strong> " . number_format($donhang['tongtien'] ?? 0, 0, ',', '.') . " VNĐ<br>";

                            // Hiển thị nút hủy nếu trạng thái phù hợp
                            if (in_array($donhang['tinhtrang'], ['Chờ thanh toán', 'Chờ xử lý'])) {
                                echo "<div class='action-links'>";
                                echo "<a href='#' onclick='toggleCancelForm(" . $donhang['madh'] . ")'>Hủy đơn hàng</a>";
                                echo "</div>";
                                echo "<div class='cancel-form' id='cancel-form-" . $donhang['madh'] . "'>";
                                echo "<form method='POST' onsubmit='showCancelSuccess()'>";
                                echo "<input type='hidden' name='madh' value='" . $donhang['madh'] . "'>";
                                echo "<input type='hidden' name='custom_order_id' value='" . htmlspecialchars($donhang['custom_order_id']) . "'>";
                                echo "<select name='lydo_huy' required>";
                                echo "<option value='' disabled selected>Chọn lý do hủy</option>";
                                echo "<option value='Nhập sai thông tin giao hàng'>Nhập sai thông tin giao hàng</option>";
                                echo "<option value='Thay đổi ý định, không muốn mua nữa'>Thay đổi ý định, không muốn mua nữa</option>";
                                echo "<option value='Sản phẩm không đúng mô tả'>Sản phẩm không đúng mô tả</option>";
                                echo "<option value='Tìm thấy giá tốt hơn ở nơi khác'>Tìm thấy giá tốt hơn ở nơi khác</option>";
                                echo "<option value='Thời gian giao hàng quá lâu'>Thời gian giao hàng quá lâu</option>";
                                echo "<option value='Không đủ khả năng thanh toán'>Không đủ khả năng thanh toán</option>";
                                echo "<option value='Sản phẩm hết hàng hoặc không có sẵn'>Sản phẩm hết hàng hoặc không có sẵn</option>";
                                echo "<option value='Đặt nhầm sản phẩm'>Đặt nhầm sản phẩm</option>";
                                echo "<option value='Lý do cá nhân'>Lý do cá nhân</option>";
                                echo "<option value='Khác'>Khác</option>";
                                echo "</select>";
                                echo "<button type='submit' name='cancel_order'>Xác nhận hủy</button>";
                                echo "</form>";
                                echo "</div>";
                            }

                            $madh = $donhang['madh'];
                            $stmt_ct = $connect->prepare("
                                SELECT ct.madh, ct.soluong, sp.tensp, sp.giaban
                                FROM chitietdonhang ct 
                                JOIN sanpham sp ON ct.masp = sp.masp 
                                WHERE ct.madh = ?
                            ");
                            $stmt_ct->bind_param("i", $madh);
                            $stmt_ct->execute();
                            $result_ct = $stmt_ct->get_result();

                            echo "<table>";
                            echo "<tr><th>Mã Đơn Hàng</th><th>Sản phẩm</th><th>Số lượng</th><th>Đơn giá</th><th>Thành tiền</th></tr>";
                            while ($ct = $result_ct->fetch_assoc()) {
                                $thanhtien = $ct['soluong'] * $ct['giaban'];
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($display_order_id) . "</td>";
                                echo "<td>" . htmlspecialchars($ct['tensp']) . "</td>";
                                echo "<td>" . htmlspecialchars($ct['soluong']) . "</td>";
                                echo "<td>" . number_format($ct['giaban'], 0, ',', '.') . " VNĐ</td>";
                                echo "<td>" . number_format($thanhtien, 0, ',', '.') . " VNĐ</td>";
                                echo "</tr>";
                            }
                            echo "</table>";
                            echo "</div>";
                            $stmt_ct->close();
                        }
                    } else {
                        echo "<p>Bạn chưa có đơn hàng nào hoặc tất cả đơn hàng đã bị hủy.</p>";
                    }
                    $stmt_dh->close();
                    break;

                case 'info':
                    echo "<h2>Thông tin cá nhân</h2>";
                    $stmt_info = $connect->prepare("SELECT hoten, Gender, dienthoai, email, diachi, ngaydangky FROM khachhang WHERE makh = ?");
                    $stmt_info->bind_param("i", $makh);
                    $stmt_info->execute();
                    $result = $stmt_info->get_result();
                    $kh = $result->fetch_assoc();
                
                    echo '<div class="info-group">';
                    echo '<p><strong>Họ tên:</strong> ' . htmlspecialchars($kh['hoten'] ?? 'Không xác định') . '</p>';
                    echo '<p><strong>Giới tính:</strong> ' . htmlspecialchars($kh['Gender'] ?? 'Chưa cập nhật') . '</p>';
                    echo '<p><strong>Điện thoại:</strong> ' . htmlspecialchars($kh['dienthoai'] ?? 'Chưa cập nhật') . '</p>';
                    echo '<p><strong>Email:</strong> ' . htmlspecialchars($kh['email'] ?? '') . '</p>';
                    echo '<p><strong>Địa chỉ:</strong> ' . htmlspecialchars($kh['diachi'] ?? 'Chưa cập nhật') . '</p>';
                    echo '<p><strong>Ngày đăng ký:</strong> ' . htmlspecialchars($kh['ngaydangky'] ?? '') . '</p>';
                    echo '</div>';
                    $stmt_info->close();
                    break;

                case 'password':
                    echo "<h2>Thay đổi mật khẩu</h2>";
                    echo '<form method="POST">
                            <div class="info-group">
                                <label for="old_pass">Mật khẩu cũ:</label>
                                <input type="password" name="old_pass" id="old_pass" placeholder="Nhập mật khẩu cũ" required>
                            </div>
                            <div class="info-group">
                                <label for="new_pass">Mật khẩu mới:</label>
                                <input type="password" name="new_pass" id="new_pass" placeholder="Nhập mật khẩu mới" required>
                            </div>
                            <button type="submit" class="btn btn-block">Đổi mật khẩu</button>
                          </form>';
                    break;

                case 'saved':
                    echo "<h2>Sản phẩm đang lưu</h2>";
                    $stmt_gh = $connect->prepare("
                        SELECT gh.magh, ct.masp, ct.soluong, sp.tensp, sp.giaban, sp.hinhanh
                        FROM giohang gh
                        JOIN chitietgiohang ct ON gh.magh = ct.magh
                        JOIN sanpham sp ON ct.masp = sp.masp
                        WHERE gh.makh = ? AND gh.hieuluc = 1
                    ");
                    $stmt_gh->bind_param("i", $makh);
                    $stmt_gh->execute();
                    $result_gh = $stmt_gh->get_result();

                    if ($result_gh->num_rows > 0) {
                        echo "<table class='table'>";
                        echo "<tr><th>Hình ảnh</th><th>Sản phẩm</th><th>Số lượng</th><th>Đơn giá</th><th>Thành tiền</th></tr>";
                        while ($item = $result_gh->fetch_assoc()) {
                            $thanhtien = $item['soluong'] * $item['giaban'];
                            echo "<tr>";
                            echo "<td><img src='/Project1_Product/uploads/" . htmlspecialchars($item['hinhanh']) . "' alt='Hình sản phẩm' style='width: 50px; height: auto;'></td>";
                            echo "<td>" . htmlspecialchars($item['tensp']) . "</td>";
                            echo "<td>" . htmlspecialchars($item['soluong']) . "</td>";
                            echo "<td>" . number_format($item['giaban'], 0, ',', '.') . " VNĐ</td>";
                            echo "<td>" . number_format($thanhtien, 0, ',', '.') . " VNĐ</td>";
                            echo "</tr>";
                        }
                        echo "</table>";
                    } else {
                        echo "<p>Không có sản phẩm nào trong giỏ hàng.</p>";
                    }
                    $stmt_gh->close();
                    break;

                case 'config':
                    echo "<h2>Cấu hình của tôi</h2>";
                    $stmt_config = $connect->prepare("SELECT hoten, Gender, diachi, dienthoai FROM khachhang WHERE makh = ?");
                    $stmt_config->bind_param("i", $makh);
                    $stmt_config->execute();
                    $result_config = $stmt_config->get_result();
                    $kh = $result_config->fetch_assoc();
                    $stmt_config->close();
                
                    echo '<form method="POST">';
                    echo '<div class="info-group">';
                    echo '<label>Họ tên</label>';
                    echo '<input type="text" name="hoten" value="' . htmlspecialchars($kh['hoten'] ?? '') . '" required>';
                    echo '</div>';
                    echo '<div class="info-group">';
                    echo '<label>Địa chỉ</label>';
                    echo '<input type="text" name="diachi" value="' . htmlspecialchars($kh['diachi'] ?? '') . '">';
                    echo '</div>';
                    echo '<div class="info-group">';
                    echo '<label>Điện thoại</label>';
                    echo '<input type="text" name="dienthoai" value="' . htmlspecialchars($kh['dienthoai'] ?? '') . '">';
                    echo '</div>';
                    echo '<div class="info-group">';
                    echo '<label>Giới tính</label>';
                    echo '<div class="gender-group">';
                    echo '<label class="male"><input type="radio" name="Gender" value="Nam" ' . ($kh['Gender'] === 'Nam' ? 'checked' : '') . '> Nam</label>';
                    echo '<label class="female"><input type="radio" name="Gender" value="Nữ" ' . ($kh['Gender'] === 'Nữ' ? 'checked' : '') . '> Nữ</label>';
                    echo '</div>';
                    echo '</div>';
                    echo '<button type="submit" class="btn btn-block">Lưu thay đổi</button>';
                    echo '</form>';
                    break;
                default:
                    echo "<p>Nội dung không tồn tại.</p>";
            }
            ?>
        </div>
    </div>

    <script src="/Project1_Product/js/header.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const savedTheme = localStorage.getItem('theme');
        const systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        
        if (savedTheme === 'dark' || (!savedTheme && systemPrefersDark)) {
            document.body.classList.add('dark-mode');
            document.querySelector('.theme-switch input').checked = true;
        }
        
        const themeSwitch = document.querySelector('.theme-switch input');
        if (themeSwitch) {
            themeSwitch.addEventListener('change', function() {
                if (this.checked) {
                    document.body.classList.add('dark-mode');
                    localStorage.setItem('theme', 'dark');
                } else {
                    document.body.classList.remove('dark-mode');
                    localStorage.setItem('theme', 'light');
                }
            });
        }
    });

    function toggleCancelForm(orderId) {
        const form = document.getElementById('cancel-form-' + orderId);
        form.style.display = form.style.display === 'block' ? 'none' : 'block';
    }

    function showCancelSuccess() {
        console.log("Hủy đơn hàng thành công");
    }
    </script>
</body>
</html>

<?php
$connect->close();
ob_end_flush();
?>
<?php include $_SERVER['DOCUMENT_ROOT'] . '/Project1_Product/footer.php'; ?>