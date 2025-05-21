<?php
include $_SERVER['DOCUMENT_ROOT']  . '/Project1_Product/connect.php';
// bọc session 
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
$admin_id = $_SESSION['admin_id']; 
$hanhdong = "Xóa Khách Hàng ";
$thoigian = date('Y-m-d H:i:s');

$sql_log = "INSERT INTO admin_log (admin_id, hanhdong, thoigian) VALUES (?, ?, ?)";
$stmt = $connect->prepare($sql_log);
$stmt->bind_param("iss", $admin_id, $hanhdong, $thoigian);
$stmt->execute();

$makh = (int) $_GET['makh'];
$del = "DELETE FROM khachhang WHERE makh = $makh";
mysqli_query($connect,$del);
header("Location:/Project1_Product/quanly_khachhang.php");
?>
