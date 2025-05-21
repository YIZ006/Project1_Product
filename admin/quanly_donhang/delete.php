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

// Get order ID from URL
$madh = isset($_GET['madh']) ? (int)$_GET['madh'] : 0;

if ($madh <= 0) {
    header("Location: /Project1_Product/admin/quanly_donhang/quanly_donhang.php?error=InvalidOrderID");
    exit;
}

// Log the delete action
$hanhdong = "Xóa đơn hàng có mã: $madh";
$thoigian = date('Y-m-d H:i:s');
$sql_log = "INSERT INTO admin_log (admin_id, hanhdong, thoigian) VALUES (?, ?, ?)";
$stmt_log = $connect->prepare($sql_log);
$stmt_log->bind_param("iss", $admin_id, $hanhdong, $thoigian);
$stmt_log->execute();
$stmt_log->close();

// Delete the order
$sql_delete = "DELETE FROM donhang WHERE madh = ?";
$stmt_delete = $connect->prepare($sql_delete);
$stmt_delete->bind_param("i", $madh);
$stmt_delete->execute();

// Check if deletion was successful
if ($stmt_delete->affected_rows > 0) {
    header("Location: /Project1_Product/admin/quanly_donhang/quanly_donhang.php?success=OrderDeleted");
} else {
    header("Location: /Project1_Product/admin/quanly_donhang/quanly_donhang.php?error=OrderNotFound");
}

$stmt_delete->close();
$connect->close();
?>