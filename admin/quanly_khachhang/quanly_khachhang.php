<?php
// Khởi động session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Kiểm tra đăng nhập
if (!isset($_SESSION['admin_id'])) {
    header("Location: /Project1_Product/admin/login.php");
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
$records_per_page = 5; // Số bản ghi mỗi trang
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Đếm tổng số bản ghi
$sql_count = "SELECT COUNT(*) AS total FROM khachhang";
$result_count = $connect->query($sql_count);
$total_records = $result_count->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Lấy dữ liệu cho trang hiện tại
$sql = "SELECT * FROM khachhang LIMIT ? OFFSET ?";
$stmt = $connect->prepare($sql);
$stmt->bind_param("ii", $records_per_page, $offset);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý khách hàng</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="/Project1_Product/styles/admin/quanly_khachhang.css">
</head>

<body>
    <h1>Quản lý khách hàng</h1>
    <?php include(__DIR__ . '/../dashboard.php'); ?>

    <div class="content">
        <table>
            <thead>
                <tr>
                    <th>Mã KH</th>
                    <th>Họ Tên</th>
                    <th>Địa Chỉ</th>
                    <th>Email</th>
                    <th>Điện Thoại</th>
                    <th>Ngày Đăng Ký</th>
                    <th>Hành Động</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['makh']) ?></td>
                        <td><?= htmlspecialchars($row['hoten']) ?></td>
                        <td><?= htmlspecialchars($row['diachi']) ?></td>
                        <td><?= htmlspecialchars($row['email']) ?></td>
                        <td><?= htmlspecialchars($row['dienthoai']) ?></td>
                        <td><?= htmlspecialchars($row['ngaydangky']) ?></td>
                        <td>
                            <a href="edit_khachhang.php?id=<?= $row['makh'] ?>">Sửa</a> |
                            <a href="delete_khachhang.php?id=<?= $row['makh'] ?>" onclick="return confirm('Bạn có chắc muốn xóa?')">Xóa</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <!-- Phân trang -->
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?= $page - 1 ?>">Previous</a>
            <?php else: ?>
                <a href="#" class="disabled">Previous</a>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=<?= $i ?>" class="<?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>

            <?php if ($page < $total_pages): ?>
                <a href="?page=<?= $page + 1 ?>">Next</a>
            <?php else: ?>
                <a href="#" class="disabled">Next</a>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>
<?php
$stmt->close();
$connect->close();
?>