<?php
// include 'header.php';
include $_SERVER['DOCUMENT_ROOT'] .'/Project1_Product/connect.php';
// bọc session 
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$admin_id = $_SESSION['admin_id']; 
$hanhdong = "Cập nhật dữ liệu của khách hàng";
$thoigian = date('Y-m-d H:i:s');

$sql_log = "INSERT INTO admin_log (admin_id, hanhdong, thoigian) VALUES (?, ?, ?)";
$stmt = $connect->prepare($sql_log);
$stmt->bind_param("iss", $admin_id, $hanhdong, $thoigian);
$stmt->execute();

$makh = $_GET['makh'];
$result = mysqli_query($connect, "SELECT * FROM khachhang WHERE makh = $makh");
$khachhang = mysqli_fetch_assoc($result);

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $hoten = $_POST['hoten'];
    $diachi = $_POST['diachi'];
    $email = $_POST['email'];
    $dienthoai = $_POST['dienthoai'];
    $ngaydangky = $_POST['ngaydangky'];

    mysqli_query($connect, "UPDATE khachhang SET hoten='$hoten', diachi='$diachi',email='$email', dienthoai='$dienthoai',ngaydangky='$ngaydangky' WHERE makh=$makh");
    header("Location:/Project1_Product/admin/quanly_khachhang.php");
}

// Lấy danh sách khách hàng
$result = mysqli_query($connect, "SELECT * FROM khachhang");

?>

<form action="" method="post">
    <label for="">Fullname:</label>
    <input type="text" name="hoten" value="<?= htmlspecialchars($khachhang['hoten']) ?>" required>

    <label for="">Address:</label>
    <input type="text" name="diachi" value="<?= htmlspecialchars($khachhang['diachi']) ?>" required>

    <label for="">Email:</label>
    <input type="text" name="email" value="<?= htmlspecialchars($khachhang['email']) ?>" required>

    <label for="">Phone</label>
    <input type="text" name="dienthoai" value="<?= htmlspecialchars($khachhang['dienthoai'])?>"required>

    <label for="">Date_Register</label>
    <input type="text" name="ngaydangky" value="<?= htmlspecialchars($khachhang['ngaydangky'])?>"required>
    <!-- <label for="">Trạng Thái:</label>
     -->
    <button type="submit">Cập nhật</button>
</form>

