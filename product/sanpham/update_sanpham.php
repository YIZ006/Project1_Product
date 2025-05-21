<?php
include $_SERVER['DOCUMENT_ROOT'] . '/Project1_Product/connect.php';
// bọc session 
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
$thongbao = '';
$tensp = $giaban = $giamgia = $soluong = $maloai = $mota = $giagoc = $diem_danhgia = '';
$hinhanh = '';

// Kiểm tra tham số đầu vào
if (!isset($_GET['masp'])) {
    echo "❌ Thiếu mã sản phẩm.";
    exit;
}

$masp = (int) $_GET['masp'];

// Lấy thông tin sản phẩm
$stmt = $connect->prepare("SELECT * FROM sanpham WHERE masp = ?");
$stmt->bind_param("i", $masp);
$stmt->execute();
$result = $stmt->get_result();
$sanpham = $result->fetch_assoc();

if (!$sanpham) {
    echo "❌ Không tìm thấy sản phẩm.";
    exit;
}

// Gán giá trị từ sản phẩm vào biến
$tensp = $sanpham['tensp'];
$giaban = $sanpham['giaban'];
$giamgia = $sanpham['giamgia'];
$soluong = $sanpham['soluongton'];
$maloai = $sanpham['maloai'];
$mota = $sanpham['mota'];
$giagoc = $sanpham['giagoc'];
$diem_danhgia = $sanpham['diem_danhgia'];
$hinhanh = $sanpham['hinhanh']; // Giữ lại hình ảnh cũ

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tensp = $_POST['tensp'];
    $giaban = $_POST['giaban'];
    $giamgia = $_POST['giamgia'] ?? 0;
    $soluong = $_POST['soluong'];
    $maloai = $_POST['maloai'];
    $mota = $_POST['mota'];
    $giagoc = $_POST['giagoc'] ?? 0;
    $diem_danhgia = $_POST['diem_danhgia'] ?? 0;

    // Kiểm tra upload hình ảnh mới
    if (isset($_FILES['hinhanh']) && $_FILES['hinhanh']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $hinhanh = basename($_FILES['hinhanh']['name']);
        move_uploaded_file($_FILES['hinhanh']['tmp_name'], $uploadDir . $hinhanh);
    } else {
        // Nếu không có hình ảnh mới, giữ lại hình ảnh cũ
        $hinhanh = $sanpham['hinhanh'];
    }

    // Cập nhật sản phẩm
    $update_sql = "UPDATE sanpham SET tensp = ?, giaban = ?, soluongton = ?, hinhanh = ?, mota = ?, maloai = ?, giamgia = ?, giagoc = ?, diem_danhgia = ? WHERE masp = ?";
    $stmt = $connect->prepare($update_sql);
    $stmt->bind_param("siissiiiii", $tensp, $giaban, $soluong, $hinhanh, $mota, $maloai, $giamgia, $giagoc, $diem_danhgia, $masp);

    if ($stmt->execute()) {
        // Ghi log sau khi cập nhật thành công
        $admin_id = $_SESSION['admin_id'] ?? 0; // Lấy ID quản trị viên từ session

        // Kiểm tra xem admin_id có tồn tại trong bảng admin không
        $check_admin_sql = "SELECT * FROM admin WHERE admin_id = ?";
        $stmt_check = $connect->prepare($check_admin_sql);
        $stmt_check->bind_param("i", $admin_id);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows > 0) {
            $hanhdong = "Cập nhật sản phẩm có mã: $masp"; // Hành động ghi log
            $thoigian = date('Y-m-d H:i:s'); // Thời gian hiện tại

            // Ghi log vào bảng admin_log
            $log_sql = "INSERT INTO admin_log (admin_id, hanhdong, thoigian) VALUES (?, ?, ?)";
            $stmt_log = $connect->prepare($log_sql);
            $stmt_log->bind_param("iss", $admin_id, $hanhdong, $thoigian);
            $stmt_log->execute(); // Thực thi ghi log
        } else {
            echo "❌ Không tìm thấy quản trị viên với ID: $admin_id.";
        }

        $thongbao = "<p style='color: green;'>✅ Cập nhật sản phẩm thành công!</p>";
        // Xoá dữ liệu cũ sau khi thêm
        $tensp = $giaban = $giamgia = $soluong = $maloai = $mota = $giagoc = $diem_danhgia = '';
    } else {
        $thongbao = "<p style='color: red;'>❌ Lỗi khi cập nhật: " . $stmt->error . "</p>";
    }
}

?>
<h2>Cập nhật sản phẩm</h2>
<?= $thongbao ?>

<form method="POST" enctype="multipart/form-data">
    <label for="">Tên sản phẩm: </label><input type="text" name="tensp" value="<?= htmlspecialchars($tensp) ?>" required><br><br>
    <label for="">Giá bán:</label> <input type="number" step="0.01" name="giaban" value="<?= htmlspecialchars($giaban) ?>" required><br><br>
    <label for="">Giá gốc:</label><input type="number" step="0.01" name="giagoc" value="<?= htmlspecialchars($giagoc) ?>" required><br><br>
    <label for="">Giảm giá (%):</label><input type="number" step="0.01" name="giamgia" value="<?= htmlspecialchars($giamgia) ?>"><br><br>
    <label for="">Số lượng:</label> <input type="number" name="soluong" value="<?= htmlspecialchars($soluong) ?>" required><br><br>
    <label for="">Điểm đánh giá:</label><input type="number" step="0.01" name="diem_danhgia" value="<?= htmlspecialchars($diem_danhgia) ?>"><br><br>
    <label for="">Hình ảnh:</label> <input type="file" name="hinhanh"><br><br> <!-- Cho phép upload hình ảnh mới -->
    <label for="">Mô tả:</label><br><textarea name="mota" rows="4" cols="50"><?= htmlspecialchars($mota) ?></textarea><br><br>
    <label for="">Mã loại:</label>
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
    <button type="submit">Cập nhật</button>
</form>

<a href="./quanly_sanpham.php">⬅ Quay về danh sách</a>