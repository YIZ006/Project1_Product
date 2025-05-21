<?php
ob_start();
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include $_SERVER['DOCUMENT_ROOT'] . '/Project1_Product/connect.php';

// Xử lý đăng xuất
if (isset($_GET['logout'])) {
    if (isset($_SESSION['makh'])) {
        $makh = $_SESSION['makh'];
        $hanhdong = "Đăng xuất khách hàng (makh: $makh)";
        $thoigian = date('Y-m-d H:i:s');
        $sql_log = "INSERT INTO customer_log (makh, hanhdong, thoigian) VALUES (?, ?, ?)";
        $stmt = mysqli_prepare($connect, $sql_log);
        mysqli_stmt_bind_param($stmt, "iss", $makh, $hanhdong, $thoigian);
        if (!mysqli_stmt_execute($stmt)) {
            error_log("Lỗi ghi log đăng xuất: " . mysqli_stmt_error($stmt));
        }
        mysqli_stmt_close($stmt);
    }
    session_destroy();
    header("Location: /Project1_Product/index.php");
    exit;
}

// Xử lý đăng nhập
$login_error = '';
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === 'login') {
    $email = trim($_POST['email']);
    $password = $_POST['matkhau'];

    if (empty($email) || empty($password)) {
        $login_error = "Vui lòng nhập đầy đủ email và mật khẩu!";
    } else {
        $sql = "SELECT makh, hoten, matkhau FROM khachhang WHERE email = ?";
        $stmt = mysqli_prepare($connect, $sql);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($result && mysqli_num_rows($result) > 0) {
            $kh = mysqli_fetch_assoc($result);
            if (password_verify($password, $kh['matkhau'])) {
                $_SESSION['makh'] = $kh['makh'];
                $_SESSION['hoten'] = $kh['hoten'];
                
                // Ghi log đăng nhập
                $hanhdong = "Đăng nhập khách hàng thành công (Email: $email, makh: {$kh['makh']})";
                $thoigian = date('Y-m-d H:i:s');
                $sql_log = "INSERT INTO customer_log (makh, hanhdong, thoigian) VALUES (?, ?, ?)";
                $stmt_log = mysqli_prepare($connect, $sql_log);
                mysqli_stmt_bind_param($stmt_log, "iss", $kh['makh'], $hanhdong, $thoigian);
                if (!mysqli_stmt_execute($stmt_log)) {
                    error_log("Lỗi ghi log đăng nhập: " . mysqli_stmt_error($stmt_log));
                }
                mysqli_stmt_close($stmt_log);

                header("Location: /Project1_Product/index.php");
                exit;
            } else {
                $login_error = "Mật khẩu không đúng!";
            }
        } else {
            $login_error = "Không tìm thấy tài khoản với email này!";
        }
        mysqli_stmt_close($stmt);

        // Ghi log đăng nhập thất bại
        $hanhdong = "Đăng nhập khách hàng thất bại (Email: $email)";
        $thoigian = date('Y-m-d H:i:s');
        $sql_log = "INSERT INTO customer_log (makh, hanhdong, thoigian) VALUES (NULL, ?, ?)";
        $stmt_log = mysqli_prepare($connect, $sql_log);
        mysqli_stmt_bind_param($stmt_log, "ss", $hanhdong, $thoigian);
        if (!mysqli_stmt_execute($stmt_log)) {
            error_log("Lỗi ghi log đăng nhập thất bại: " . mysqli_stmt_error($stmt_log));
        }
        mysqli_stmt_close($stmt_log);
    }
}

// Xử lý đăng ký
$register_error = '';
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === 'register') {
    $hoten = trim($_POST['hoten']);
    $email = trim($_POST['email']);
    $password_raw = $_POST['matkhau'];

    if (empty($hoten) || empty($email) || empty($password_raw)) {
        $register_error = "Vui lòng nhập đầy đủ thông tin!";
    } else {
        // Kiểm tra email đã tồn tại
        $check_sql = "SELECT makh FROM khachhang WHERE email = ?";
        $stmt = mysqli_prepare($connect, $check_sql);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) > 0) {
            $register_error = "Email đã tồn tại! Vui lòng chọn email khác.";
        } else {
            $password = password_hash($password_raw, PASSWORD_DEFAULT);
            $ngaydangky = date('Y-m-d');
            $insert_sql = "INSERT INTO khachhang (hoten, email, matkhau, ngaydangky) VALUES (?, ?, ?, ?)";
            $stmt_insert = mysqli_prepare($connect, $insert_sql);
            mysqli_stmt_bind_param($stmt_insert, "ssss", $hoten, $email, $password, $ngaydangky);

            if (mysqli_stmt_execute($stmt_insert)) {
                $makh = mysqli_insert_id($connect);
                
                // Ghi log đăng ký
                $hanhdong = "Đăng ký khách hàng thành công (Email: $email, makh: $makh)";
                $thoigian = date('Y-m-d H:i:s');
                $sql_log = "INSERT INTO customer_log (makh, hanhdong, thoigian) VALUES (?, ?, ?)";
                $stmt_log = mysqli_prepare($connect, $sql_log);
                mysqli_stmt_bind_param($stmt_log, "iss", $makh, $hanhdong, $thoigian);
                if (!mysqli_stmt_execute($stmt_log)) {
                    error_log("Lỗi ghi log đăng ký: " . mysqli_stmt_error($stmt_log));
                }
                mysqli_stmt_close($stmt_log);

                $register_error = "Đăng ký thành công! Vui lòng đăng nhập.";
            } else {
                $register_error = "Đăng ký thất bại: " . mysqli_error($connect);
                error_log("Lỗi đăng ký: " . mysqli_error($connect));
            }
            mysqli_stmt_close($stmt_insert);
        }
        mysqli_stmt_close($stmt);
    }
}

// Kiểm tra trạng thái đăng nhập
$is_logged_in = isset($_SESSION['makh']);
$display_name = $is_logged_in ? $_SESSION['hoten'] : 'Đăng Nhập';
?>

    <!-- Modal Đăng Nhập -->
    <div class="modal-overlay" id="login-modal">
        <div class="modal-content">
            <span class="close-btn"><i class="fas fa-times"></i></span>
            <h3>Đăng Nhập</h3>
            <?php if (!empty($login_error)): ?>
                <div class="error"><?php echo htmlspecialchars($login_error); ?></div>
            <?php endif; ?>
            <form method="POST">
                <input type="hidden" name="action" value="login">
                <div class="form-group">
                    <label for="login-email">Email</label>
                    <input type="email" id="login-email" name="email" placeholder="Nhập email của bạn" required />
                </div>
                <div class="form-group">
                    <label for="login-matkhau">Mật khẩu</label>
                    <input type="password" id="login-matkhau" name="matkhau" placeholder="Nhập mật khẩu" required />
                </div>
                <button type="submit">Đăng Nhập</button>
                <a href="mailto:phamc13579@gmail.com" class="link">Quên mật khẩu?</a>
            </form>
        </div>
    </div>

    <!-- Modal Đăng Ký -->
    <div class="modal-overlay" id="register-modal">
        <div class="modal-content">
            <span class="close-btn"><i class="fas fa-times"></i></span>
            <h3>Đăng Ký</h3>
            <?php if (!empty($register_error)): ?>
                <div class="<?php echo strpos($register_error, 'thành công') !== false ? 'success' : 'error'; ?>">
                    <?php echo htmlspecialchars($register_error); ?>
                </div>
            <?php endif; ?>
            <form method="POST">
                <input type="hidden" name="action" value="register">
                <div class="form-group">
                    <label for="register-hoten">Họ và tên</label>
                    <input type="text" id="register-hoten" name="hoten" placeholder="Nhập họ và tên" required />
                </div>
                <div class="form-group">
                    <label for="register-email">Email</label>
                    <input type="email" id="register-email" name="email" placeholder="Nhập email của bạn" required />
                </div>
                <div class="form-group">
                    <label for="register-matkhau">Mật khẩu</label>
                    <input type="password" id="register-matkhau" name="matkhau" placeholder="Nhập mật khẩu" required />
                </div>
                <button type="submit">Đăng Ký</button>
                <a href="#" class="link open-login-modal">Đã có tài khoản? Đăng nhập</a>
            </form>
        </div>
    </div>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Megatech - Web Bán Máy Tính</title>
    <link rel="stylesheet" href="/Project1_Product/styles/global.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
   <link rel="stylesheet" href="/Project1_Product/styles/header_index.css">
   <!-- <link rel="stylesheet" href="/Project1_Product/styles/footer_index.css"> -->
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
                        <li><a href="/Project1_Product/customer/profile.php">Trang cá nhân</a></li>
                        <li><a href="/Project1_Product/customer/cart.php">Giỏ hàng</a></li>
                    </ul>
                </li>
                <li class="dropdown">
                    <a href="/Project1_Product/cart/cart.php"><img src="https://cdn-icons-png.flaticon.com/512/107/107831.png" width="24" alt="Giỏ hàng" /></a>
                </li>
                <li class="dropdown">
                    <a href="#" class="toggle-dropdown"><i class="fas fa-user-circle user-icon"></i> <?php echo htmlspecialchars($display_name); ?></a>
                    <ul class="dropdown-content">
                        <?php if ($is_logged_in): ?>
                            <li><a href="/Project1_Product/customer/profile_customer.php">Tài Khoản</a></li>
                            <li><a href="/Project1_Product/index.php?logout=1">Đăng Xuất</a></li>
                        <?php else: ?>
                            <li><span>Xin chào, vui lòng đăng nhập</span></li>
                            <li><a href="#" class="open-login-modal">Đăng Nhập</a></li>
                            <li><a href="#" class="open-register-modal">Đăng Ký</a></li>
                        <?php endif; ?>
                    </ul>
                </li>
            </ul>
        </div>
    </div>


    <!-- link JavaScript -->
     <script src="/Project1_Product/js/header.js"></script>
     
</body>
</html>

<?php
mysqli_close($connect);
ob_end_flush();
?>