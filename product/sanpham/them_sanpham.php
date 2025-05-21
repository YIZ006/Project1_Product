<?php
include(__DIR__ . '/../../connect.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tensp = $_POST['tensp'];
    $giaban = (int)$_POST['giaban'];
    $giagoc = (int)($_POST['giagoc'] ?? 0);
    $giamgia = (int)($_POST['giamgia'] ?? 0);
    $diem_danhgia = (int)($_POST['diem_danhgia'] ?? 0);
    $soluongton = (int)$_POST['soluongton'];
    $maloai = (int)$_POST['maloai'];
    $mota = $_POST['mota'];

    // Upload hình ảnh
    $hinhanh = '';
    if (isset($_FILES['hinhanh']) && $_FILES['hinhanh']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $hinhanh = basename($_FILES['hinhanh']['name']);
        move_uploaded_file($_FILES['hinhanh']['tmp_name'], $uploadDir . $hinhanh);
    }

    // Thêm vào CSDL
    $sql = "INSERT INTO sanpham (tensp, giaban, soluongton, maloai, mota, giamgia, giagoc, diem_danhgia, hinhanh)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $connect->prepare($sql);
    $stmt->bind_param(
        "siiisiiis",
        $tensp,
        $giaban,
        $soluongton,
        $maloai,
        $mota,
        $giamgia,
        $giagoc,
        $diem_danhgia,
        $hinhanh
    );

    if ($stmt->execute()) {
        echo "✅ Thêm sản phẩm thành công!";
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        $admin_id = $_SESSION['admin_id'] ?? 0;
        $hanhdong = "Thêm danh mục có mã: $maloai";
        $log_sql = "INSERT INTO admin_log (admin_id, hanhdong, thoigian) VALUES (?, ?, NOW())";
        $log_stmt = mysqli_prepare($connect, $log_sql);
        mysqli_stmt_bind_param($log_stmt, "is", $admin_id, $hanhdong);
        mysqli_stmt_execute($log_stmt);
    } else {
        echo "❌ Lỗi: " . $stmt->error;
    }
}
?>

<h2>Thêm Sản Phẩm</h2>
<form method="POST" enctype="multipart/form-data">
    Tên sản phẩm: <input type="text" name="tensp" required><br><br>
    Giá bán: <input type="number" name="giaban" required><br><br>
    Giá gốc: <input type="number" name="giagoc"><br><br>
    Giảm giá (%): <input type="number" name="giamgia" value="0"><br><br>
    Điểm đánh giá: <input type="number" name="diem_danhgia" value="0"><br><br>
    Số lượng: <input type="number" name="soluongton" required><br><br>
    Hình ảnh: <input type="file" name="hinhanh"><br><br>
    Mô tả:<br><textarea name="mota" rows="4" cols="50"></textarea><br><br>
    Mã loại sản phẩm:
    <select name="maloai" required>
        <?php
        // Lấy danh sách loại sản phẩm từ bảng loaisanpham
        $query_loai = "SELECT maloai, mota FROM loaisanpham";
        $result_loai = mysqli_query($connect, $query_loai);
        while ($row_loai = mysqli_fetch_assoc($result_loai)) {
            echo "<option value='" . $row_loai['maloai'] . "'>" . htmlspecialchars($row_loai['mota']) . "</option>";
        }
        ?>
    </select>
    <br><br>
        <button type="submit">Thêm sản phẩm</button>
</form>
<a href="/Project1_Product/product/sanpham/quanly_sanpham.php">Về trang quản lý sản phẩm</a>