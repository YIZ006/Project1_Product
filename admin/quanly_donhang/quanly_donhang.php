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
$records_per_page = 8; // Số bản ghi mỗi trang
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Lấy giá trị tìm kiếm
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Đếm tổng số bản ghi (có tìm kiếm)
$sql_count = "SELECT COUNT(*) AS total FROM donhang WHERE madh LIKE ? OR custom_order_id LIKE ?";
$stmt_count = $connect->prepare($sql_count);
$search_param = "%$search%";
$stmt_count->bind_param("ss", $search_param, $search_param);
$stmt_count->execute();
$result_count = $stmt_count->get_result();
$total_records = $result_count->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Truy vấn lấy danh sách đơn hàng (có tìm kiếm và phân trang)
$sql = "SELECT madh, makh, ngaytao, soluong, tennguoinhan, diachi, sodienthoai, phuongthuctt, tinhtrang, ngaydat, custom_order_id 
        FROM donhang 
        WHERE madh LIKE ? OR custom_order_id LIKE ? 
        LIMIT ? OFFSET ?";
$stmt = $connect->prepare($sql);
$stmt->bind_param("ssii", $search_param, $search_param, $records_per_page, $offset);
$stmt->execute();
$result = $stmt->get_result();

$stt = $offset + 1; // Số thứ tự bắt đầu từ offset
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý đơn hàng</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="/Project1_Product/styles/admin/quanly_donhang.css">
</head>

<body>
    <h1>Quản lý đơn hàng</h1>
    <?php include(__DIR__ . '/../dashboard.php'); ?>

    <div class="content">
        <!-- Form tìm kiếm -->
        <form action="" method="get" class="search-form">
            <label for="search">Tìm kiếm đơn hàng:</label>
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Nhập mã đơn hàng">
            <input type="submit" value="Tìm kiếm">
            <input type="reset" value="Nhập lại">
        </form>

        <!-- Thông báo -->
        <?php
        if (isset($_GET['success'])) {
            if ($_GET['success'] == 'OrderDeleted') {
                echo '<p style="color: green;">Đơn hàng đã được xóa thành công!</p>';
            } elseif ($_GET['success'] == 'OrderUpdated') {
                echo '<p style="color: green;">Đơn hàng đã được cập nhật thành công!</p>';
            }
        } elseif (isset($_GET['error'])) {
            if ($_GET['error'] == 'InvalidOrderID') {
                echo '<p style="color: red;">Mã đơn hàng không hợp lệ!</p>';
            } elseif ($_GET['error'] == 'OrderNotFound') {
                echo '<p style="color: red;">Không tìm thấy đơn hàng!</p>';
            }
        }
        ?>

        <!-- Bảng hiển thị -->
        <table>
            <thead>
                <tr>
                    <th>STT</th>
                    <th>Mã Hiển Thị</th>
                    <th>Mã Khách Hàng</th>
                    <th>Tên Người Nhận</th>
                    <th>Địa Chỉ Giao</th>
                    <th>Số Điện Thoại</th>
                    <th>Phương Thức Thanh Toán</th>
                    <th>Ngày Tạo</th>
                    <th>Số Lượng</th>
                    <th>Tình Trạng</th>
                    <th>Ngày Đặt</th>
                    <th>Hành Động</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= $stt++ ?></td>
                            <td>
                                <?php 
                                // Hiển thị 8 chữ số cuối của custom_order_id nếu có, nếu không thì madh với định dạng 8 chữ số
                                echo $row['custom_order_id'] ? htmlspecialchars(substr($row['custom_order_id'], -8)) : sprintf("%08d", $row['madh']); 
                                ?>
                            </td>
                            <td><?= htmlspecialchars($row['makh']) ?></td>
                            <td><?= htmlspecialchars($row['tennguoinhan']) ?></td>
                            <td><?= htmlspecialchars($row['diachi']) ?></td>
                            <td><?= htmlspecialchars($row['sodienthoai']) ?></td>
                            <td><?= htmlspecialchars($row['phuongthuctt']) ?></td>
                            <td><?= htmlspecialchars($row['ngaytao']) ?></td>
                            <td><?= htmlspecialchars($row['soluong']) ?></td>
                            <td><?= htmlspecialchars($row['tinhtrang']) ?></td>
                            <td><?= htmlspecialchars($row['ngaydat']) ?></td>
                            <td class="action-links">
                                <a href="update.php?madh=<?= $row['madh'] ?>">Sửa</a> 
                                <a href="delete.php?madh=<?= $row['madh'] ?>" onclick="return confirm('Bạn có chắc muốn xóa?')">Xóa</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="12">Không có đơn hàng nào</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Phân trang -->
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>">Previous</a>
            <?php else: ?>
                <a href="#" class="disabled">Previous</a>
            <?php endif; ?>

            <?php
            $range = 2; // Hiển thị 2 trang trước và sau trang hiện tại
            $start = max(1, $page - $range);
            $end = min($total_pages, $page + $range);

            for ($i = $start; $i <= $end; $i++): ?>
                <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>" class="<?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>

            <?php if ($page < $total_pages): ?>
                <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>">Next</a>
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