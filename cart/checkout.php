<?php
session_start();
include $_SERVER['DOCUMENT_ROOT'] . '/Project1_Product/connect.php';

// Kiểm tra session makh
if (isset($_SESSION['makh'])) {
    $makh = $_SESSION['makh'];
    // echo "Mã khách hàng (makh): " . $makh . "<br>"; // Debug
} else {
    echo "Bạn cần đăng nhập để thanh toán.";
    exit;
}

// Đồng bộ giỏ hàng từ session (nếu có)
if (isset($_SESSION['cart'])) {
    $sql_giohang = "SELECT magh FROM giohang WHERE makh = $makh";
    $result_giohang = $connect->query($sql_giohang);

    if ($result_giohang->num_rows == 0) {
        $sql_tao_giohang = "INSERT INTO giohang (makh) VALUES ($makh)";
        $connect->query($sql_tao_giohang);
        $magh = $connect->insert_id;
    } else {
        $row = $result_giohang->fetch_assoc();
        $magh = $row['magh'];
    }

    foreach ($_SESSION['cart'] as $masp => $soluong) {
        $sql_chitiet = "INSERT INTO chitietgiohang (magh, masp, soluong) VALUES ($magh, $masp, $soluong)
                        ON DUPLICATE KEY UPDATE soluong = soluong + $soluong";
        $connect->query($sql_chitiet);
    }
    unset($_SESSION['cart']);
}
// Lấy giỏ hàng của khách hàng
$stmt_giohang = $connect->prepare("SELECT magh FROM giohang WHERE makh = ? AND hieuluc = 1");
$stmt_giohang->bind_param("i", $makh);
$stmt_giohang->execute();
$result_giohang = $stmt_giohang->get_result();

if ($result_giohang->num_rows > 0) {
    $row_giohang = $result_giohang->fetch_assoc();
    $magh = $row_giohang['magh'];

    // Lấy chi tiết giỏ hàng
    $stmt_chitiet = $connect->prepare("SELECT ct.soluong, sp.tensp, sp.giaban, sp.masp, sp.soluongton 
                                      FROM chitietgiohang ct 
                                      JOIN sanpham sp ON ct.masp = sp.masp 
                                      WHERE ct.magh = ?");
    $stmt_chitiet->bind_param("i", $magh);
    $stmt_chitiet->execute();
    $result_chitiet = $stmt_chitiet->get_result();

    if ($result_chitiet->num_rows == 0) {
        echo "Giỏ hàng của bạn không có sản phẩm nào (magh = $magh).";
        exit;
    }
} else {
    echo "Không tìm thấy giỏ hàng cho khách hàng với makh = $makh.";
    exit;
}
$stmt_giohang->close();
$stmt_chitiet->close();
// Xử lý thanh toán
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Làm sạch dữ liệu đầu vào
    $tennguoinhan = mysqli_real_escape_string($connect, $_POST['tennguoinhan']);
    $diachi = mysqli_real_escape_string($connect, $_POST['diachi']);
    $sodienthoai = mysqli_real_escape_string($connect, $_POST['sodienthoai']);
    $phuongthuctt = mysqli_real_escape_string($connect, $_POST['phuongthuctt']);
    $tongtien = 0;
    $tongSoLuong = 0;

    // Tính tổng tiền và tổng số lượng
    if ($result_chitiet->num_rows > 0) {
        $result_chitiet->data_seek(0);
        while ($row = $result_chitiet->fetch_assoc()) {
            $tongtien += $row['soluong'] * $row['giaban'];
            $tongSoLuong += $row['soluong'];

            // Kiểm tra số lượng tồn kho
            if ($row['soluong'] > $row['soluongton']) {
                echo "Sản phẩm " . $row['tensp'] . " không đủ số lượng tồn kho. Chỉ còn " . $row['soluongton'] . " sản phẩm.";
                exit;
            }
        }
    }

    // Tạo đơn hàng
    $ngaydathang = date("Y-m-d");
    $sql_donhang = "INSERT INTO donhang (makh, ngaydat, soluong, tinhtrang, tongtien, tennguoinhan, diachi, sodienthoai, phuongthuctt) 
                    VALUES ('$makh', '$ngaydathang', '$tongSoLuong', 'Chờ xử lý', '$tongtien', '$tennguoinhan', '$diachi', '$sodienthoai', '$phuongthuctt')";
    
    if ($connect->query($sql_donhang) === TRUE) {
        $madh = $connect->insert_id;

        // Lưu chi tiết đơn hàng và cập nhật số lượng tồn kho
        $result_chitiet->data_seek(0);
        while ($row = $result_chitiet->fetch_assoc()) {
            $masp = $row['masp'];
            $soluong = $row['soluong'];

            // Lưu chi tiết đơn hàng
            $sql_chitietdh = "INSERT INTO chitietdonhang (madh, masp, soluong) 
                              VALUES ('$madh', '$masp', '$soluong')";
            $connect->query($sql_chitietdh);

            // Cập nhật số lượng tồn kho
            $new_soluongton = $row['soluongton'] - $soluong;
            $sql_update_soluong = "UPDATE sanpham SET soluongton = $new_soluongton WHERE masp = $masp";
            $connect->query($sql_update_soluong);
        }

        // Xóa giỏ hàng sau khi thanh toán
        $sql_xoact = "DELETE FROM chitietgiohang WHERE magh = $magh";
        $connect->query($sql_xoact);
        $sql_xoagh = "DELETE FROM giohang WHERE magh = $magh";
        $connect->query($sql_xoagh);

        // Chuyển hướng đến trang xác nhận
        header("Location: confirmation.php?madh=$madh");
        exit;
    } else {
        echo "Lỗi khi tạo đơn hàng: " . $connect->error;
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh Toán</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; }
        h2 { text-align: center; }
        .container { width: 80%; margin: 0 auto; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; }
        input, select { width: 100%; padding: 8px; box-sizing: border-box; }
        button { background-color: #4CAF50; color: white; padding: 10px 15px; border: none; cursor: pointer; }
        button:hover { background-color: #45a049; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: center; }
        th { background-color: #f2f2f2; }
        .total { font-weight: bold; text-align: right; }
    </style>
</head>
<body>
    <h2>Thanh Toán</h2>
    <div class="container">
        <h3>Thông tin đơn hàng</h3>
        <table>
            <tr>
                <th>Tên Sản Phẩm</th>
                <th>Số Lượng</th>
                <th>Giá</th>
                <th>Thành Tiền</th>
            </tr>
            <?php
            $tongtien = 0;
            if ($result_chitiet->num_rows > 0) {
                $result_chitiet->data_seek(0);
                while ($row = $result_chitiet->fetch_assoc()) {
                    $thanhtien = $row['soluong'] * $row['giaban'];
                    $tongtien += $thanhtien;
                    echo "<tr>";
                    echo "<td>" . $row['tensp'] . "</td>";
                    echo "<td>" . $row['soluong'] . "</td>";
                    echo "<td>" . number_format($row['giaban'], 0, ',', '.') . " VNĐ</td>";
                    echo "<td>" . number_format($thanhtien, 0, ',', '.') . " VNĐ</td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='4'>Không có sản phẩm trong giỏ hàng.</td></tr>";
            }
            ?>
        </table>
        <p class="total">Tổng tiền: <?php echo number_format($tongtien, 0, ',', '.'); ?> VNĐ</p>

        <h3>Thông tin giao hàng</h3>
        <form method="POST" action="">
            <div class="form-group">
                <label for="tennguoinhan">Tên người nhận:</label>
                <input type="text" id="tennguoinhan" name="tennguoinhan" required>
            </div>
            <div class="form-group">
                <label for="diachi">Địa chỉ giao hàng:</label>
                <input type="text" id="diachi" name="diachi" required>
            </div>
            <div class="form-group">
                <label for="sodienthoai">Số điện thoại:</label>
                <input type="text" id="sodienthoai" name="sodienthoai" required>
            </div>
            <div class="form-group">
                <label for="phuongthuctt">Phương thức thanh toán:</label>
                <select id="phuongthuctt" name="phuongthuctt" required>
                    <option value="COD">Thanh toán khi nhận hàng (COD)</option>
                    <option value="ChuyenKhoan">Chuyển khoản ngân hàng</option>
                </select>
            </div>
            <button type="submit">Xác nhận thanh toán</button>
        </form>
    </div>
    <?php $connect->close(); ?>
</body>
</html>