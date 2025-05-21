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
    $hanhdong = "Tìm kiếm danh mục với từ khóa: " . $search;
    $thoigian = date('Y-m-d H:i:s');
    $stmt = $connect->prepare("INSERT INTO admin_log (admin_id, hanhdong, thoigian) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $admin_id, $hanhdong, $thoigian);
    $stmt->execute();
}

// Lấy giá trị sắp xếp
$sort = isset($_GET['sort']) && in_array($_GET['sort'], ['maloai', 'tenloai', 'mota', 'giaban', 'soluongton']) ? $_GET['sort'] : 'maloai';
$order = isset($_GET['order']) && $_GET['order'] == 'desc' ? 'DESC' : 'ASC';

// Đếm tổng số bản ghi
$sql_count = "SELECT COUNT(*) AS total FROM loaisanpham WHERE mota LIKE ? OR tenloai LIKE ?";
$stmt_count = $connect->prepare($sql_count);
$search_param = "%$search%";
$stmt_count->bind_param("ss", $search_param, $search_param);
$stmt_count->execute();
$result_count = $stmt_count->get_result();
$total_records = $result_count->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Truy vấn lấy danh sách danh mục
$sql = "SELECT maloai, tenloai, mota, giaban, soluongton, hinhanh 
        FROM loaisanpham 
        WHERE mota LIKE ? OR tenloai LIKE ? 
        ORDER BY $sort $order 
        LIMIT ? OFFSET ?";
$stmt = $connect->prepare($sql);
$stmt->bind_param("ssii", $search_param, $search_param, $records_per_page, $offset);
$stmt->execute();
$result = $stmt->get_result();

$stt = $offset + 1;
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý danh mục</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="/Project1_Product/styles/product/danhmuc.css">
</head>
<body>
    <h1>Quản lý danh mục</h1>
    
    <?php include_once('../../admin/dashboard.php'); ?>
    <div class="messages">
    <?php if (isset($_GET['success']) && $_GET['success'] == 'deleted'): ?>
        <p class="success">Xóa danh mục thành công!</p>
    <?php elseif (isset($_GET['error'])): ?>
        <p class="error">
            <?php
            switch ($_GET['error']) {
                case 'missing_id': echo 'Thiếu mã danh mục!'; break;
                case 'not_found': echo 'Danh mục không tồn tại!'; break;
                case 'linked': echo 'Không thể xóa vì danh mục có sản phẩm liên quan!'; break;
                case 'delete_failed': echo 'Xóa danh mục thất bại!'; break;
                case 'invalid_method': echo 'Phương thức không hợp lệ!'; break;
                default: echo 'Đã xảy ra lỗi!';
            }
            ?>
        </p>
    <?php endif; ?>
</div>
    <div class="content">
        <a href="add_danhmuc.php" class="add-new">+ Thêm danh mục</a>
        <form method="get" class="search-form">
            <input type="text" name="search" placeholder="Tìm kiếm theo tên hoặc mô tả..." value="<?= htmlspecialchars($search) ?>">
            <button type="submit">Tìm</button>
        </form>

        <table>
            <thead>
                <tr>
                    <th><a href="?sort=maloai&order=<?= $sort == 'maloai' && $order == 'ASC' ? 'desc' : 'asc' ?>&search=<?= urlencode($search) ?>">ID</a></th>
                    <th><a href="?sort=tenloai&order=<?= $sort == 'tenloai' && $order == 'ASC' ? 'desc' : 'asc' ?>&search=<?= urlencode($search) ?>">Tên Loại</a></th>
                    <th><a href="?sort=mota&order=<?= $sort == 'mota' && $order == 'ASC' ? 'desc' : 'asc' ?>&search=<?= urlencode($search) ?>">Mô tả</a></th>
                    <!-- <th><a href="?sort=giaban&order=<?= $sort == 'giaban' && $order == 'ASC' ? 'desc' : 'asc' ?>&search=<?= urlencode($search) ?>">Giá bán</a></th> -->
                    <th><a href="?sort=soluongton&order=<?= $sort == 'soluongton' && $order == 'ASC' ? 'desc' : 'asc' ?>&search=<?= urlencode($search) ?>">Số lượng tồn</a></th>
                    <th>Hình ảnh</th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                         
                            <td><?= htmlspecialchars($row['maloai']) ?></td>
                            <td><?= htmlspecialchars($row['tenloai']) ?></td>
                            <td><?= htmlspecialchars($row['mota']) ?></td>
                            <!-- <td><?= number_format($row['giaban'], 0, ',', '.') ?></td> -->
                            <td><?= htmlspecialchars($row['soluongton']) ?></td>
                            <td><img src="../../Uploads/<?= htmlspecialchars($row['hinhanh']) ?>" width="80"></td>
                            <td class="action-links">
                                <a href="update_danhmuc.php?maloai=<?= $row['maloai'] ?>">Sửa</a> |
                                <a href="/Project1_Product/product/danhmuc/xoa_danhmuc.php?maloai=<?= $row['maloai'] ?>" onclick="return confirm('Bạn chắc chắn muốn xóa?')">Xóa</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="7">Không tìm thấy danh mục nào</td></tr>
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