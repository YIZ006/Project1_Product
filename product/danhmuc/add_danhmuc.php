<?php
include(__DIR__ . '/../../connect.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $maloai = $_POST['maloai'];
    $mota = $_POST['mota'];
    $giaban = $_POST['giaban'];
    $soluongton = $_POST['soluongton'];

    $hinhanh = '';
    if (isset($_FILES['hinhanh']) && $_FILES['hinhanh']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $hinhanh = basename($_FILES['hinhanh']['name']);
        move_uploaded_file($_FILES['hinhanh']['tmp_name'], $uploadDir . $hinhanh);
    }

    // Kiểm tra mã loại đã tồn tại chưa
    $check_exist_sql = "SELECT * FROM loaisanpham WHERE maloai = ?";
    $stmt = mysqli_prepare($connect, $check_exist_sql);
    mysqli_stmt_bind_param($stmt, "i", $maloai);
    mysqli_stmt_execute($stmt);
    $result_exist = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result_exist) > 0) {
        echo "❌ Lỗi: Mã loại <strong>$maloai</strong> đã tồn tại. Vui lòng chọn mã khác.";
    } else {
        // Tiến hành thêm mới
        $sql = "INSERT INTO loaisanpham (mota, giaban, soluongton, hinhanh, maloai)
                VALUES (?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($connect, $sql);
        mysqli_stmt_bind_param($stmt, "sddsi", $mota, $giaban, $soluongton, $hinhanh, $maloai);
        $result = mysqli_stmt_execute($stmt);

        if ($result) {
            // Ghi log hành động thành công
            // bọc session 
            if (session_status() == PHP_SESSION_NONE) {
                session_start();
            }
            $admin_id = $_SESSION['admin_id'] ?? 0;
            $hanhdong = "Thêm danh mục có mã: $maloai";
            $log_sql = "INSERT INTO admin_log (admin_id, hanhdong, thoigian) VALUES (?, ?, NOW())";
            $log_stmt = mysqli_prepare($connect, $log_sql);
            mysqli_stmt_bind_param($log_stmt, "is", $admin_id, $hanhdong);
            mysqli_stmt_execute($log_stmt);

            echo "✅ Thêm danh mục thành công!";
        } else {
            echo "❌ Lỗi khi thêm danh mục: " . mysqli_error($connect);
        }
    }
}
?>

<h2>Thêm Danh Mục</h2>
<form method="POST" enctype="multipart/form-data">
    Mô tả: <input type="text" name="mota" required><br><br>
    Giá bán: <input type="number" step="0.01" name="giaban" required><br><br>
    Số lượng tồn: <input type="number" step="0.01" name="soluongton" value="0"><br><br>
    Mã loại sản phẩm: <input type="number" name="maloai" required><br><br>
    Hình ảnh: <input type="file" name="hinhanh" accept="image/*" required><br><br>
    <button type="submit">Thêm danh mục</button>
</form>
<a href="./quanly_danhmuc.php">Quay về trang danh mục</a>