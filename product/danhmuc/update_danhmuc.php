<?php
include $_SERVER['DOCUMENT_ROOT'] . '/Project1_Product/connect.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_GET['maloai'])) {
    echo "❌ Thiếu mã loại.";
    exit;
}

$maloai = (int) $_GET['maloai'];

// Lấy thông tin danh mục
$stmt = $connect->prepare("SELECT * FROM loaisanpham WHERE maloai = ?");
$stmt->bind_param("i", $maloai);
$stmt->execute();
$result = $stmt->get_result();
$loaisanpham = $result->fetch_assoc();

if (!$loaisanpham) {
    echo "❌ Không tìm thấy danh mục.";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === "POST") {
    $mota = $_POST['mota'];
    $giaban = $_POST['giaban'];
    $soluongton = $_POST['soluongton'];

    // Xử lý hình ảnh
    $hinhanh = $loaisanpham['hinhanh']; // giữ lại nếu không upload mới
    if (isset($_FILES['hinhanh']) && $_FILES['hinhanh']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $hinhanh = basename($_FILES['hinhanh']['name']);
        move_uploaded_file($_FILES['hinhanh']['tmp_name'], $uploadDir . $hinhanh);
    }

    $update_sql = "UPDATE loaisanpham SET mota = ?, giaban = ?, soluongton = ?, hinhanh = ? WHERE maloai = ?";
    $stmt = $connect->prepare($update_sql);
    $stmt->bind_param("sddsi", $mota, $giaban, $soluongton, $hinhanh, $maloai);
    $success = $stmt->execute();

    if ($success) {
        $admin_id = $_SESSION['admin_id'] ?? 0;
        $hanhdong = "Cập nhật danh mục có mã: $maloai";
        $thoigian = date('Y-m-d H:i:s');

        $log_sql = "INSERT INTO admin_log (admin_id, hanhdong, thoigian) VALUES (?, ?, ?)";
        $stmt_log = $connect->prepare($log_sql);
        $stmt_log->bind_param("iss", $admin_id, $hanhdong, $thoigian);
        $stmt_log->execute();

        header("Location: /Project1_Product/product/danhmuc/quanly_danhmuc.php");
        exit;
    } else {
        echo "❌ Cập nhật thất bại: " . $connect->error;
    }
}
?>

<h2>Cập nhật danh mục</h2>
<form action="" method="post" enctype="multipart/form-data">
    <p><strong>Mã loại: <?= htmlspecialchars($loaisanpham['maloai']) ?></strong></p>
    <input type="hidden" name="maloai" value="<?= htmlspecialchars($loaisanpham['maloai']) ?>">

    <label>Mô tả</label><br>
    <input type="text" name="mota" value="<?= htmlspecialchars($loaisanpham['mota']) ?>" required><br><br>

    <label>Giá bán</label><br>
    <input type="number" step="0.01" name="giaban" value="<?= htmlspecialchars($loaisanpham['giaban']) ?>" required><br><br>

    <label>Số lượng tồn</label><br>
    <input type="number" step="1" name="soluongton" value="<?= htmlspecialchars($loaisanpham['soluongton']) ?>" required><br><br>

    <label>Hình ảnh (chọn để thay đổi):</label><br>
    <input type="file" name="hinhanh"><br>
    <img src="../uploads/<?= htmlspecialchars($loaisanpham['hinhanh']) ?>" width="120"><br><br>

    <button type="submit">Cập nhật</button>
</form>

<a href="./quanly_danhmuc.php">⬅ Quay về danh sách</a>
