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

// Lấy giá trị sắp xếp
$sort = isset($_GET['sort']) && in_array($_GET['sort'], ['admin_id', 'username', 'hoten', 'email', 'dienthoai', 'quyen', 'ngaytao']) ? $_GET['sort'] : 'admin_id';
$order = isset($_GET['order']) && $_GET['order'] == 'desc' ? 'DESC' : 'ASC';

// Đếm tổng số bản ghi
$sql_count = "SELECT COUNT(*) AS total FROM admin WHERE username LIKE ? OR hoten LIKE ? OR email LIKE ? OR dienthoai LIKE ? OR quyen LIKE ? OR ngaytao LIKE ?";
$stmt_count = $connect->prepare($sql_count);
$search_param = "%$search%";
$stmt_count->bind_param("ssssss", $search_param, $search_param, $search_param, $search_param, $search_param, $search_param);
$stmt_count->execute();
$result_count = $stmt_count->get_result();
$total_records = $result_count->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Truy vấn lấy danh sách admin
$sql = "SELECT admin_id, username, password, hoten, email, dienthoai, quyen, ngaytao 
        FROM admin 
        WHERE username LIKE ? OR hoten LIKE ? OR email LIKE ? OR dienthoai LIKE ? OR quyen LIKE ? OR ngaytao LIKE ? 
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
    <title>Quản lý tài khoản quản trị</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="/Project1_Product/styles/admin/taikhoan_quantri.css">
</head>

<body>
    <h1>Quản lý tài khoản quản trị</h1>
    <?php include(__DIR__ . '/../dashboard.php'); ?>
    
    <div class="content">
        <!-- Form tìm kiếm -->
        <form action="" method="get" class="search-form">
            <label for="search">Tìm kiếm admin:</label>
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Nhập tên, email, số điện thoại, quyền...">
            <input type="submit" value="Tìm kiếm">
            <input type="reset" value="Nhập lại">
            <a href="add_admin.php" class="add-new">Thêm mới</a>
        </form>

        <!-- Bảng hiển thị -->
        <table>
            <thead>
                <tr><th>STT</th>
                    <th><a href="?sort=admin_id&order=<?= $sort == 'admin_id' && $order == 'ASC' ? 'desc' : 'asc' ?>&search=<?= urlencode($search) ?>">ID</a></th>
                    <th><a href="?sort=username&order=<?= $sort == 'username' && $order == 'ASC' ? 'desc' : 'asc' ?>&search=<?= urlencode($search) ?>">Username</a></th>
                    <th><a href="?sort=hoten&order=<?= $sort == 'hoten' && $order == 'ASC' ? 'desc' : 'asc' ?>&search=<?= urlencode($search) ?>">Họ Tên</a></th>
                    <th><a href="?sort=email&order=<?= $sort == 'email' && $order == 'ASC' ? 'desc' : 'asc' ?>&search=<?= urlencode($search) ?>">Email</a></th>
                    <th><a href="?sort=dienthoai&order=<?= $sort == 'dienthoai' && $order == 'ASC' ? 'desc' : 'asc' ?>&search=<?= urlencode($search) ?>">Điện Thoại</a></th>
                    <th><a href="?sort=quyen&order=<?= $sort == 'quyen' && $order == 'ASC' ? 'desc' : 'asc' ?>&search=<?= urlencode($search) ?>">Quyền</a></th>
                    <th><a href Wilhelmshaven="?sort=ngaytao&order=<?= $sort == 'ngaytao' && $order == 'ASC' ? 'desc' : 'asc' ?>&search=<?= urlencode($search) ?>">Ngày Tạo</a></th>
                    <th>Hành Động</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= $stt++ ?></td>
                            <td><?= htmlspecialchars($row['admin_id']) ?></td>
                            <td><?= htmlspecialchars($row['username']) ?></td>
                            <td><?= htmlspecialchars($row['hoten']) ?></td>
                            <td><?= htmlspecialchars($row['email']) ?></td>
                            <td><?= htmlspecialchars($row['dienthoai']) ?></td>
                            <td><?= htmlspecialchars($row['quyen']) ?></td>
                            <td><?= htmlspecialchars($row['ngaytao']) ?></td>
                            <td class="action-links">
                                <a href="edit_admin.php?id=<?= $row['admin_id'] ?>">Sửa</a> 
                                <a href="delete_admin.php?id=<?= $row['admin_id'] ?>" onclick="return confirm('Bạn có chắc muốn xóa?')">Xóa</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="10">Không có tài khoản nào</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Phân trang -->
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