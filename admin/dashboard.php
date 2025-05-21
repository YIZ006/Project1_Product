<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trang quản trị</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="/Project1_Product/styles/admin/dashboard.css">
</head>

<body>

    <!-- <h1>Đây là trang hệ thống quản trị dữ liệu</h1> -->
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/Project1_Product/connect.php'; ?>

    <?php
// bọc session 
if (session_status() == PHP_SESSION_NONE) {
  session_start();
}

  // Kiểm tra nếu chưa đăng nhập thì đá về login
  if (!isset($_SESSION['admin_id'])) {
    header("Location: /Project1_Product/admin/login.php");
    exit;
  }

  // Giả sử admin đã đăng nhập và lưu id trong session
  if (isset($_SESSION['admin_id']) && !isset($_SESSION['login_logged'])) {
    $admin_id = $_SESSION['admin_id'];
    $hanhdong = "Đăng nhập hệ thống";
    $thoigian = date('Y-m-d H:i:s');

    $sql_log = "INSERT INTO admin_log (admin_id, hanhdong, thoigian) VALUES (?, ?, ?)";
    $stmt = $connect->prepare($sql_log);
    $stmt->bind_param("iss", $admin_id, $hanhdong, $thoigian);
    $stmt->execute();

    // Ghi nhận đã log để không lặp lại
    $_SESSION['login_logged'] = true;
  }

  // Lấy biến ra từ session
  $admin_id = $_SESSION['admin_id'];
  $username = $_SESSION['username'];
  $quyen = $_SESSION['quyen'];
  ?>

    <div class="menu-container">
        <div class="menu">
            <!-- Menu chính -->
            <ul class="menu-list">
                <li class="dropdown">
                    <a href="#"><img src="https://cdn-icons-png.flaticon.com/512/1828/1828859.png" width="24"
                            alt="Menu" /></a>
                    <ul class="dropdown-content">
                        <li><a href="/Project1_Product/admin/quanly_donhang/quanly_donhang.php">Quản lý Đơn hàng</a></li>
                        <li><a href="/Project1_Product/product/sanpham/quanly_sanpham.php">Quản lý Sản phẩm</a></li>
                        <li><a href="/Project1_Product/admin/quanly_khachhang/quanly_khachhang.php">Quản lý Khách hàng</a></li>
                        <li><a href="/Project1_Product/admin/taikhoan_quantri/taikhoan_quantri.php">Tài khoản Quản trị</a></li>
                        <li><a href="/Project1_Product/product/danhmuc/quanly_danhmuc.php">Quản lý danh mục</a></li>
                    </ul>
                </li>
            </ul>

            <!-- User Dropdown -->
            <ul class="menu-list user">
                <li class="dropdown">
                    <a href="#"><i class="fas fa-user-circle user-icon"></i></a>
                    <ul class="dropdown-content">
                        <li><span>👋 Xin chào Admin <?= htmlspecialchars($username) ?> (ID: <?= $admin_id ?>)
                            </span></li>
                        <li><span style="font-size: 12px;">🔑 Quyền: <?= htmlspecialchars($quyen) ?></span></li>
                        <li><a href="/Project1_Product/admin/lichsu_hoatdong.php">Lịch sử hoạt động </a></li>
                        <a href="/Project1_Product/admin/login.php?logout=1" id="logout">Đăng Xuất</a>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</body>

</html>