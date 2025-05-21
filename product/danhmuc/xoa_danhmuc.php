<?php
session_start();
include $_SERVER['DOCUMENT_ROOT'] . '/Project1_Product/connect.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['admin_id'])) {
    header("Location: /Project1_Product/admin/login_admin.php");
    exit;
}

// Đường dẫn cơ sở
$base_url = "/Project1_Product/product/danhmuc/quanly_danhmuc.php";

// Chỉ cho phép GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header("Location: $base_url?error=invalid_method");
    exit;
}

// Kiểm tra tham số maloai
if (!isset($_GET['maloai'])) {
    header("Location: $base_url?error=missing_id");
    exit;
}

$maloai = (int) $_GET['maloai'];

// Kiểm tra danh mục tồn tại
$check_exist_sql = "SELECT * FROM loaisanpham WHERE maloai = ?";
$stmt = mysqli_prepare($connect, $check_exist_sql);
mysqli_stmt_bind_param($stmt, "i", $maloai);
mysqli_stmt_execute($stmt);
$result_exist = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result_exist) === 0) {
    header("Location: $base_url?error=not_found");
    exit;
}

// Kiểm tra sản phẩm liên quan
$check_sql = "SELECT COUNT(*) AS total FROM sanpham WHERE maloai = ?";
$stmt = mysqli_prepare($connect, $check_sql);
mysqli_stmt_bind_param($stmt, "i", $maloai);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($result);

if ($row['total'] > 0) {
    header("Location: $base_url?error=linked");
    exit;
}

// Thực hiện xóa
$delete_sql = "DELETE FROM loaisanpham WHERE maloai = ?";
$stmt = mysqli_prepare($connect, $delete_sql);
mysqli_stmt_bind_param($stmt, "i", $maloai);
if (mysqli_stmt_execute($stmt)) {
    // Ghi log
    $admin_id = $_SESSION['admin_id'];
    $hanhdong = "Xóa danh mục có mã: $maloai";
    $log_sql = "INSERT INTO admin_log (admin_id, hanhdong, thoigian) VALUES (?, ?, NOW())";
    $stmt_log = mysqli_prepare($connect, $log_sql);
    mysqli_stmt_bind_param($stmt_log, "is", $admin_id, $hanhdong);
    mysqli_stmt_execute($stmt_log);
    mysqli_stmt_close($stmt_log);
    header("Location: $base_url?success=deleted");
} else {
    header("Location: $base_url?error=delete_failed");
}
mysqli_stmt_close($stmt);
mysqli_close($connect);
exit;
?>