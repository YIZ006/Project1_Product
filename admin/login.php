<?php
// bọc session 
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include $_SERVER['DOCUMENT_ROOT'] . '/Project1_Product/connect.php';

// Xử lý logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: /Project1_Product/admin/login.php");
    exit;
}

// Xử lý đăng nhập
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM admin WHERE username = '$username'";
    $result = mysqli_query($connect, $sql);

    if ($result && mysqli_num_rows($result) > 0) {
        $admin = mysqli_fetch_assoc($result);

        if (password_verify($password, $admin['password'])) {
            $_SESSION['admin_id'] = $admin['admin_id'];
            $_SESSION['username'] = $admin['username'];
            $_SESSION['quyen'] = $admin['quyen'];

            header("Location: /Project1_Product/admin/dashboard.php");
            exit;
        } else {
            $error = "Mật khẩu không đúng!";
        }
    } else {
        $error = "Không tìm thấy tài khoản!";
    }
}
?>

<!-- HTML phần đăng nhập -->
<h2>Đăng nhập hệ thống quản trị</h2>

<?php if (!empty($error)) echo "<p style='color:red;'>$error</p>"; ?>

<form method="POST">
    Username: <input type="text" name="username" required><br>
    Password: <input type="password" name="password" required><br><br>
    <button type="submit">Đăng nhập</button>
    <a href="mailto:phamc13579@gmail.com" title="Liên hệ admin để lấy lại mật khẩu">Quên Mật Khẩu</a>
    <a href="/Project1_Product/admin/admin_register.php">Chưa có tài khoản?</a>
</form>
