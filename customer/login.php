<?php
// Khởi động session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Kết nối database
include $_SERVER['DOCUMENT_ROOT'] . '/Project1_Product/connect.php';

// Xử lý đăng xuất
if (isset($_GET['logout'])) {
    if (isset($_SESSION['makh'])) {
        $makh = $_SESSION['makh'];
        $hanhdong = "Đăng xuất khách hàng";
        $thoigian = date('Y-m-d H:i:s');
        // Ghi log vào customer_log
        $stmt = $connect->prepare("INSERT INTO customer_log (makh, hanhdong, thoigian) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $makh, $hanhdong, $thoigian);
        $stmt->execute();
        $stmt->close();
    }
    session_destroy();
    header("Location: login.php");
    exit;
}

// Xử lý đăng nhập
$login_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $matkhau = $_POST['matkhau'];

    if (empty($email) || empty($matkhau)) {
        $login_message = "<div class='message error'>Vui lòng nhập đầy đủ email và mật khẩu!</div>";
    } else {
        // Sửa truy vấn: Sử dụng bảng khachhang thay vì admin_log
        $stmt = $connect->prepare("SELECT makh, hoten, matkhau FROM khachhang WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $kh = $result->fetch_assoc();

        if ($kh && password_verify($matkhau, $kh['matkhau'])) {
            // Đăng nhập thành công
            $_SESSION['makh'] = $kh['makh'];
            $_SESSION['hoten'] = $kh['hoten'];

            // Ghi log vào customer_log
            $makh = $kh['makh'];
            $hanhdong = "Đăng nhập khách hàng thành công (Email: $email, makh: $makh)";
            $thoigian = date('Y-m-d H:i:s');
            $stmt_log = $connect->prepare("INSERT INTO customer_log (makh, hanhdong, thoigian) VALUES (?, ?, ?)");
            $stmt_log->bind_param("iss", $makh, $hanhdong, $thoigian);
            $stmt_log->execute();
            $stmt_log->close();

            // Chuyển hướng tự động đến trang cá nhân hoặc trang chủ
            header("Location: /Project1_Product/customer/profile_customer.php");
            exit;
        } else {
            // Ghi log thất bại vào customer_log
            $hanhdong = "Đăng nhập khách hàng thất bại (Email: $email)";
            $thoigian = date('Y-m-d H:i:s');
            $stmt_log = $connect->prepare("INSERT INTO customer_log (makh, hanhdong, thoigian) VALUES (NULL, ?, ?)");
            $stmt_log->bind_param("ss", $hanhdong, $thoigian);
            $stmt_log->execute();
            $stmt_log->close();

            $login_message = "<div class='message error'>Email hoặc mật khẩu không đúng!</div>";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập Khách hàng</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* Giữ nguyên CSS của bạn */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Arial', sans-serif;
        }

        body {
            background-color: #f5f5f5;
            color: #333;
        }

        h1 {
            text-align: center;
            padding: 20px;
            background-color: #ecf0f1;
            color: #1E3A8A;
        }

        .menu-container {
            background-color: #1E3A8A;
        }

        .menu {
            max-width: 1200px;
            margin: auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
        }

        ul.menu-list {
            display: flex;
            list-style: none;
        }

        .menu-list li {
            position: relative;
        }

        .menu-list li a {
            display: block;
            color: white;
            text-decoration: none;
            padding: 15px;
        }

        .menu-list li a:hover {
            background-color: #34495e;
        }

        .dropdown-content {
            display: none;
            position: absolute;
            top: 100%;
            background-color: #1E3A8A;
            min-width: 200px;
            border-radius: 5px;
            box-shadow: 0px 8px 16px rgba(0, 0, 0, 0.2);
            z-index: 10;
        }

        .dropdown-content li {
            border-bottom: 1px solid #444;
            list-style: none;
        }

        .dropdown-content li a,
        .dropdown-content li span {
            display: block;
            padding: 12px 16px;
            color: white;
            text-decoration: none;
        }

        .dropdown-content li a:hover {
            background-color: #34495e;
        }

        .dropdown:hover .dropdown-content {
            display: block;
        }

        .user-icon {
            font-size: 24px;
        }

        .menu-list.user {
            flex: 1;
            justify-content: flex-end;
        }

        .login-container {
            max-width: 400px;
            margin: 40px auto;
            padding: 20px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .login-form h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #1E3A8A;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .form-group input[type="email"],
        .form-group input[type="password"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }

        .login-form button {
            width: 100%;
            padding: 10px;
            border: none;
            border-radius: 4px;
            background-color: #1E3A8A;
            color: white;
            cursor: pointer;
            font-size: 16px;
        }

        .login-form button:hover {
            background-color: #34495e;
        }

        .message {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
            text-align: center;
        }

        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .register-link, .forgot-password {
            text-align: center;
            margin-top: 10px;
        }

        .register-link a, .forgot-password a {
            color: #1E3A8A;
            text-decoration: none;
        }

        .register-link a:hover, .forgot-password a:hover {
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .menu {
                flex-direction: column;
                align-items: flex-start;
            }

            ul.menu-list {
                flex-direction: column;
                width: 100%;
            }

            .dropdown-content {
                position: static;
                width: 100%;
                box-shadow: none;
            }

            .login-container {
                margin: 20px;
                padding: 15px;
            }

            .login-form button {
                padding: 8px;
                font-size: 14px;
            }
        }

        .menu-list.user li {
            position: relative;
        }

        .menu-list.user .dropdown-content {
            right: 0;
            left: auto;
            top: 100%;
        }
    </style>
</head>
<body>
    <h1>Đăng nhập Khách hàng</h1>

    <div class="menu-container">
        <div class="menu">
            <ul class="menu-list">
                <li class="dropdown">
                    <a href="#"><img src="https://cdn-icons-png.flaticon.com/512/1828/1828859.png" width="24" alt="Menu" /></a>
                    <ul class="dropdown-content">
                        <li><a href="/Project1_Product/index.php">Trang chủ</a></li>
                        <!-- <li><a href="/Project1_Product/customer/profile_customer.php">Trang cá nhân</a></li> -->
                        <!-- <li><a href="/Project1_Product/cart/cart.php">Giỏ hàng</a></li> -->
                    </ul>
                </li>
            </ul>
            <ul class="menu-list user">
                <?php if (isset($_SESSION['makh'])): ?>
                    <li class="dropdown">
                        <a href="#"><i class="fas fa-user-circle user-icon"></i></a>
                        <ul class="dropdown-content">
                            <li><span>👋 Xin chào <?= htmlspecialchars($_SESSION['hoten']) ?></span></li>
                            <li><a href="login.php?logout=1">Đăng xuất</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li><a href="login.php">Đăng nhập</a></li>
                    <li><a href="/Project1_Product/customer/register.php">Đăng ký</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>

    <div class="login-container">
        <?php echo $login_message; ?>
        <form method="POST" class="login-form">
            <h2>Đăng nhập</h2>
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" name="email" id="email" required>
            </div>
            <div class="form-group">
                <label for="matkhau">Mật khẩu:</label>
                <input type="password" name="matkhau" id="matkhau" required>
            </div>
            <button type="submit">Đăng nhập</button>
            <p class="register-link">Chưa có tài khoản? <a href="/Project1_Product/customer/register.php">Đăng ký ngay</a></p>
            <p class="forgot-password"><a href="#">Quên mật khẩu?</a></p>
        </form>
    </div>
</body>
</html>

<?php $connect->close(); ?>
<?php include $_SERVER['DOCUMENT_ROOT']  . '/Project1_Product/footer.php';?>