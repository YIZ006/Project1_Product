<?php
// Khởi động session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Kiểm tra đăng nhập
if (!isset($_SESSION['admin_id'])) {
    header("Location: /Project1_Product/admin/login_admin.php");
    exit;
}

// Kết nối database
include $_SERVER['DOCUMENT_ROOT'] . '/Project1_Product/connect.php';

// Ghi log đăng nhập nếu chưa ghi
if (isset($_SESSION['admin_id']) && !isset($_SESSION['login_logged'])) {
    $admin_id = $_SESSION['admin_id'];
    $hanhdong = "Đăng nhập hệ thống";
    $thoigian = date('Y-m-d H:i:s');
    $sql_log = "INSERT INTO admin_log (admin_id, hanhdong, thoigian) VALUES (?, ?, ?)";
    $stmt = $connect->prepare($sql_log);
    $stmt->bind_param("iss", $admin_id, $hanhdong, $thoigian);
    $stmt->execute();
    $_SESSION['login_logged'] = true;
}

// Lấy thông tin admin
$admin_id = $_SESSION['admin_id'];
$username = $_SESSION['username'];
$quyen = $_SESSION['quyen'];

// Thiết lập phân trang
$records_per_page = 8;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Lấy giá trị tìm kiếm
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Ghi log tìm kiếm
if (!empty($search)) {
    $hanhdong = "Tìm kiếm sản phẩm với từ khóa: " . $search;
    $thoigian = date('Y-m-d H:i:s');
    $stmt = $connect->prepare("INSERT INTO admin_log (admin_id, hanhdong, thoigian) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $admin_id, $hanhdong, $thoigian);
    $stmt->execute();
}

// Lấy giá trị sắp xếp
$sort = isset($_GET['sort']) && in_array($_GET['sort'], ['masp', 'tensp', 'giaban', 'soluongton', 'mota', 'maloai', 'giagoc', 'diem_danhgia', 'giamgia']) ? $_GET['sort'] : 'masp';
$order = isset($_GET['order']) && $_GET['order'] == 'desc' ? 'DESC' : 'ASC';

// Đếm tổng số bản ghi
$sql_count = "SELECT COUNT(*) AS total 
              FROM sanpham sp 
              JOIN loaisanpham lsp ON sp.maloai = lsp.maloai 
              WHERE sp.tensp LIKE ? OR sp.giaban LIKE ? OR sp.mota LIKE ? OR sp.maloai LIKE ? OR sp.giagoc LIKE ? OR sp.diem_danhgia LIKE ?";
$stmt_count = $connect->prepare($sql_count);
$search_param = "%$search%";
$stmt_count->bind_param("ssssss", $search_param, $search_param, $search_param, $search_param, $search_param, $search_param);
$stmt_count->execute();
$result_count = $stmt_count->get_result();
$total_records = $result_count->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Truy vấn lấy danh sách sản phẩm
$sql = "SELECT sp.masp, sp.tensp, sp.giaban, sp.soluongton, sp.hinhanh, sp.mota, lsp.tenloai AS loai_mota, sp.giagoc, sp.diem_danhgia, sp.giamgia 
        FROM sanpham sp 
        JOIN loaisanpham lsp ON sp.maloai = lsp.maloai 
        WHERE sp.tensp LIKE ? OR sp.giaban LIKE ? OR sp.mota LIKE ? OR sp.maloai LIKE ? OR sp.giagoc LIKE ? OR sp.diem_danhgia LIKE ? 
        ORDER BY $sort $order 
        LIMIT ? OFFSET ?";
$stmt = $connect->prepare($sql);
$stmt->bind_param("ssssssii", $search_param, $search_param, $search_param, $search_param, $search_param, $search_param, $records_per_page, $offset);
$stmt->execute();
$result = $stmt->get_result();

$stt = $offset + 1;
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý sản phẩm</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="/Project1_Product/styles/product/sanpham.css">
</head>
<body>
    <h1>Quản lý sản phẩm</h1>
   
    <?php include_once('../../admin/dashboard.php'); ?>
    <div class="messages">
    <?php if (isset($_GET['success']) && $_GET['success'] == 'deleted'): ?>
        <p class="success">Xóa sản phẩm thành công!</p>
    <?php elseif (isset($_GET['error'])): ?>
        <p class="error">
            <?php
            switch ($_GET['error']) {
                case 'missing_id': echo 'Thiếu mã sản phẩm!'; break;
                case 'not_found': echo 'Sản phẩm không tồn tại!'; break;
                case 'linked': echo 'Không thể xóa vì sản phẩm có trong đơn hàng!'; break;
                case 'delete_failed': echo 'Xóa sản phẩm thất bại!'; break;
                case 'invalid_method': echo 'Phương thức không hợp lệ!'; break;
                default: echo 'Đã xảy ra lỗi!';
            }
            ?>
        </p>
    <?php endif; ?>
</div>

    <div class="content">
        <a href="them_sanpham.php" class="add-new">+ Thêm sản phẩm</a>
        <form action="" method="get" class="search-form">
            <label for="search">Tìm kiếm sản phẩm:</label>
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Nhập tên, giá, mô tả, mã loại...">
            <input type="submit" value="Tìm kiếm">
            <input type="reset" value="Nhập lại" onclick="window.location.href='quanly_sanpham.php'">
        </form>

        <table>
            <thead>
                <tr>
                    <th>STT</th>
                    <th><a href="?sort=masp&order=<?= $sort == 'masp' && $order == 'ASC' ? 'desc' : 'asc' ?>&search=<?= urlencode($search) ?>">Mã sản phẩm</a></th>
                    <th><a href="?sort=tensp&order=<?= $sort == 'tensp' && $order == 'ASC' ? 'desc' : 'asc' ?>&search=<?= urlencode($search) ?>">Tên sản phẩm</a></th>
                    <th><a href="?sort=giaban&order=<?= $sort == 'giaban' && $order == 'ASC' ? 'desc' : 'asc' ?>&search=<?= urlencode($search) ?>">Giá bán</a></th>
                    <th><a href="?sort=soluongton&order=<?= $sort == 'soluongton' && $order == 'ASC' ? 'desc' : 'asc' ?>&search=<?= urlencode($search) ?>">Số lượng tồn</a></th>
                    <th>Hình ảnh</th>
                    <th><a href="?sort=mota&order=<?= $sort == 'mota' && $order == 'ASC' ? 'desc' : 'asc' ?>&search=<?= urlencode($search) ?>">Mô tả</a></th>
                    <th><a href="?sort=maloai&order=<?= $sort == 'maloai' && $order == 'ASC' ? 'desc' : 'asc' ?>&search=<?= urlencode($search) ?>">Mã loại</a></th>
                    <th><a href="?sort=giagoc&order=<?= $sort == 'giagoc' && $order == 'ASC' ? 'desc' : 'asc' ?>&search=<?= urlencode($search) ?>">Giá gốc</a></th>
                    <th><a href="?sort=diem_danhgia&order=<?= $sort == 'diem_danhgia' && $order == 'ASC' ? 'desc' : 'asc' ?>&search=<?= urlencode($search) ?>">Điểm đánh giá</a></th>
                    <th><a href="?sort=giamgia&order=<?= $sort == 'giamgia' && $order == 'ASC' ? 'desc' : 'asc' ?>&search=<?= urlencode($search) ?>">Giảm giá</a></th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= $stt++ ?></td>
                            <td><?= htmlspecialchars($row['masp']) ?></td>
                            <td><?= htmlspecialchars($row['tensp']) ?></td>
                            <td><?= number_format($row['giaban'], 0, ',', '.') ?></td>
                            <td><?= htmlspecialchars($row['soluongton']) ?></td>
                            <td><img src="../Uploads/<?= htmlspecialchars($row['hinhanh']) ?>" width="80"></td>
                            <td><?= htmlspecialchars($row['mota']) ?></td>
                            <td><?= htmlspecialchars($row['loai_mota']) ?></td>
                            <td><?= number_format($row['giagoc'], 0, ',', '.') ?></td>
                            <td><?= htmlspecialchars($row['diem_danhgia']) ?></td>
                            <td><?= htmlspecialchars($row['giamgia']) ?>%</td>
                            <td class="action-links">
                                <a href="update_sanpham.php?masp=<?= $row['masp'] ?>">Sửa</a> 
                                <a href="/Project1_Product/product/sanpham/xoa_sanpham.php?masp=<?= $row['masp'] ?>" onclick="return confirm('Bạn chắc chắn muốn xóa?')">Xóa</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="11">Không có sản phẩm nào</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&sort=<?= $sort ?>&order=<?= $order ?>">Previous</a>
            <?php else: ?>
                <a href="#" class="disabled">Previous</a>
            <?php endif; ?>

            <?php
            $range = 2;
            $start = max(1, $page - $range);
            $end = min($total_pages, $page + $range);
            for ($i = $start; $i <= $end; $i++): ?>
                <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&sort=<?= $sort ?>&order=<?= $order ?>" class="<?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>

            <?php if ($page < $total_pages): ?>
                <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&sort=<?= $sort ?>&order=<?= $order ?>">Next</a>
            <?php else: ?>
                <a href="#" class="disabled">Next</a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

<?php
$stmt->close();
$stmt_count->close();
$connect->close();
?>