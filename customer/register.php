<?php
// Khởi động session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Kết nối database
include $_SERVER['DOCUMENT_ROOT'] . '/Project1_Product/connect.php';

// Xử lý đăng ký
$register_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hoten = trim($_POST['hoten']);
    $email = trim($_POST['email']);
    $matkhau = $_POST['matkhau'];
    // $diachi = trim($_POST['diachi']) ?: NULL; // Cho phép NULL nếu không nhập
    // $dienthoai = trim($_POST['dienthoai']) ?: NULL; // Cho phép NULL nếu không nhập
    $ngaydangky = date('Y-m-d');

    // Kiểm tra các trường bắt buộc
    if (empty($hoten) || empty($email) || empty($matkhau)) {
        $register_message = "<div class='message error'>Vui lòng nhập đầy đủ họ tên, email và mật khẩu!</div>";
    } else {
        // Kiểm tra email trùng lặp
        $stmt_check = $connect->prepare("SELECT makh FROM khachhang WHERE email = ?");
        $stmt_check->bind_param("s", $email);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        
        if ($result_check->num_rows > 0) {
            // Email đã tồn tại
            $hanhdong = "Đăng ký khách hàng thất bại (Email đã tồn tại: $email)";
            $thoigian = date('Y-m-d H:i:s');
            $stmt_log = $connect->prepare("INSERT INTO customer_log (makh, hanhdong, thoigian) VALUES (NULL, ?, ?)");
            $stmt_log->bind_param("ss", $hanhdong, $thoigian);
            $stmt_log->execute();
            $stmt_log->close();

            $register_message = "<div class='message error'>Email đã được đăng ký! Vui lòng sử dụng email khác.</div>";
        } else {
            // Mã hóa mật khẩu
            $matkhau_hash = password_hash($matkhau, PASSWORD_DEFAULT);

            // Chèn dữ liệu vào bảng khachhang
            $stmt = $connect->prepare("INSERT INTO khachhang (hoten, email, matkhau, diachi, dienthoai, ngaydangky) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $hoten, $email, $matkhau_hash, $diachi, $dienthoai, $ngaydangky);
            
            if ($stmt->execute()) {
                // Lấy makh vừa chèn
                $makh = $connect->insert_id;

                // Ghi log thành công
                $hanhdong = "Đăng ký khách hàng thành công (Email: $email, makh: $makh)";
                $thoigian = date('Y-m-d H:i:s');
                $stmt_log = $connect->prepare("INSERT INTO customer_log (makh, hanhdong, thoigian) VALUES (?, ?, ?)");
                $stmt_log->bind_param("iss", $makh, $hanhdong, $thoigian);
                $stmt_log->execute();
                $stmt_log->close();

                $register_message = "<div class='message success'>Đăng ký thành công! <a href='login.php'>Đăng nhập ngay</a></div>";
            } else {
                // Ghi log thất bại
                $hanhdong = "Đăng ký khách hàng thất bại (Email: $email, Lỗi: " . $connect->error . ")";
                $thoigian = date('Y-m-d H:i:s');
                $stmt_log = $connect->prepare("INSERT INTO customer_log (makh, hanhdong, thoigian) VALUES (NULL, ?, ?)");
                $stmt_log->bind_param("ss", $hanhdong, $thoigian);
                $stmt_log->execute();
                $stmt_log->close();

                $register_message = "<div class='message error'>Lỗi đăng ký: " . $connect->error . "</div>";
            }
            $stmt->close();
        }
        $stmt_check->close();
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký Khách hàng</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
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

        .form-group input[type="text"],
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

        .login-link {
            text-align: center;
            margin-top: 10px;
        }

        .login-link a {
            color: #1E3A8A;
            text-decoration: none;
        }

        .login-link a:hover {
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
    <h1>Đăng ký Khách hàng</h1>

    <div class="menu-container">
        <div class="menu">
            <ul class="menu-list">
                <li class="dropdown">
                    <a href="#"><img src="https://cdn-icons-png.flaticon.com/512/1828/1828859.png" width="24" alt="Menu" /></a>
                    <ul class="dropdown-content">
                        <li><a href="/Project1_Product/index.php">Trang chủ</a></li>
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
        <?php echo $register_message; ?>
        <form method="POST" class="login-form">
            <h2>Đăng ký</h2>
            <div class="form-group">
                <label for="hoten">Họ tên:</label>
                <input type="text" name="hoten" id="hoten" required>
            </div>
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" name="email" id="email" required>
            </div>
            <div class="form-group">
                <label for="matkhau">Mật khẩu:</label>
                <input type="password" name="matkhau" id="matkhau" required>
            </div>
            
            <button type="submit">Đăng ký</button>
            <p class="login-link">Đã có tài khoản? <a href="/Project1_Product/customer/login.php">Đăng nhập ngay</a></p>
        </form>
    </div>
</body>
</html>

<?php $connect->close(); ?>