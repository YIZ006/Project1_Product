<?php
// Khởi động session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Kết nối database
include $_SERVER['DOCUMENT_ROOT'] . '/Project1_Product/connect.php';

// Xử lý đăng xuất
if (isset($_GET['logout'])) {
    if (isset($_SESSION['admin_id'])) {
        $admin_id = $_SESSION['admin_id'];
        $hanhdong = "Đăng xuất admin";
        $thoigian = date('Y-m-d H:i:s');
        $stmt = $connect->prepare("INSERT INTO admin_log (admin_id, hanhdong, thoigian) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $admin_id, $hanhdong, $thoigian);
        $stmt->execute();
    }
    session_destroy();
    header("Location: /Project1_Product/index.php");
    exit;
}

// Xử lý đăng nhập
$login_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_submit'])) {
    $email = trim($_POST['email']);
    $matkhau = $_POST['matkhau'];

    if (empty($email) || empty($matkhau)) {
        $login_message = "<div class='login-message error'>Vui lòng nhập đầy đủ email và mật khẩu!</div>";
    } else {
        $stmt = $connect->prepare("SELECT admin_id, hoten, password FROM admin WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $admin = $result->fetch_assoc();

        if ($admin && password_verify($matkhau, $admin['password'])) {
            // Đăng nhập thành công
            $_SESSION['admin_id'] = $admin['admin_id'];
            $_SESSION['hoten'] = $admin['hoten'];

            // Ghi log
            $admin_id = $admin['admin_id'];
            $hanhdong = "Đăng nhập admin thành công";
            $thoigian = date('Y-m-d H:i:s');
            $stmt_log = $connect->prepare("INSERT INTO admin_log (admin_id, hanhdong, thoigian) VALUES (?, ?, ?)");
            $stmt_log->bind_param("iss", $admin_id, $hanhdong, $thoigian);
            $stmt_log->execute();

            // Chuyển hướng tự động đến trang quản lý
            header("Location: /Project1_Product/admin/dashboard.php");
            exit;
        } else {
            // Ghi log thất bại
            $hanhdong = "Đăng nhập admin thất bại (Email: $email)";
            $thoigian = date('Y-m-d H:i:s');
            $stmt_log = $connect->prepare("INSERT INTO admin_log (admin_id, hanhdong, thoigian) VALUES (0, ?, ?)");
            $stmt_log->bind_param("ss", $hanhdong, $thoigian);
            $stmt_log->execute();

            $login_message = "<div class='login-message error'>Email hoặc mật khẩu không đúng!</div>";
        }
        $stmt->close();
    }
}

?>
<?php
include "header.php";?>
<?php include "banner.php";?>
<?php include "product/product_display.php"; ?>
<?php include "footer.php"; ?>
