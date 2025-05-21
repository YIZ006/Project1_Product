<?php
include $_SERVER['DOCUMENT_ROOT']  . '/Project1_Product/connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password_raw = $_POST['password'];
    $hoten = $_POST['hoten'];
    $email = $_POST['email'];
    $dienthoai = $_POST['dienthoai'];
    $quyen = $_POST['quyen'];

    // Kiểm tra username đã tồn tại chưa
    $check_sql = "SELECT * FROM admin WHERE username = ?";
    $stmt = mysqli_prepare($connect, $check_sql);
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) > 0) {
        echo "<p style='color:red;'>Username đã tồn tại. Vui lòng chọn tên khác.</p>";
    } else {
        // Mã hóa mật khẩu
        $password = password_hash($password_raw, PASSWORD_DEFAULT);

        $insert_sql = "INSERT INTO admin (username, password, hoten, email, dienthoai, quyen)
                       VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($connect, $insert_sql);
        mysqli_stmt_bind_param($stmt, "ssssss", $username, $password, $hoten, $email, $dienthoai, $quyen);

        if (mysqli_stmt_execute($stmt)) {
            echo "Thêm tài khoản admin thành công! <a href='/Project1_Product/admin/login.php'>Đăng nhập</a>";
            exit;
        } else {
            echo "Lỗi khi thêm tài khoản: " . mysqli_error($connect);
        }
    }
}
?>

<!-- Giao diện đăng ký admin -->
<h2>Đăng ký tài khoản Admin</h2>
<form method="POST">
    Username: <input type="text" name="username" required><br>
    Mật khẩu: <input type="password" name="password" required><br>
    Họ tên: <input type="text" name="hoten"><br>
    Email: <input type="email" name="email"><br>
    Điện thoại: <input type="text" name="dienthoai"><br>
    Quyền: 
    <select name="quyen" required>
        <option value="admin">Quản trị</option>
        <option value="viewer">Nhân viên</option>
    </select><br><br>
    <button type="submit">Tạo tài khoản</button>
</form>
