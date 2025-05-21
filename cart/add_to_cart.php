<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include $_SERVER['DOCUMENT_ROOT'] . '/Project1_Product/connect.php';

$isAjax = isset($_GET['ajax']) && $_GET['ajax'] == 1;
$response = ['success' => false, 'message' => ''];

if (!isset($_SESSION['makh'])) {
    $response['message'] = 'Chưa đăng nhập';
    if ($isAjax) {
        echo json_encode($response);
        exit;
    } else {
        $_SESSION['error'] = "Vui lòng đăng nhập để thêm sản phẩm vào giỏ hàng.";
        header("Location: /Project1_Product/customer/login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}
$makh = (int)$_SESSION['makh'];

// Kiểm tra hoặc tạo giỏ hàng
$stmt = $connect->prepare("SELECT magh FROM giohang WHERE makh = ? AND hieuluc = 1");
$stmt->bind_param("i", $makh);
$stmt->execute();
$result_giohang = $stmt->get_result();

if ($result_giohang->num_rows == 0) {
    $stmt_taogh = $connect->prepare("INSERT INTO giohang (makh, ngaytao, hieuluc) VALUES (?, CURDATE(), 1)");
    $stmt_taogh->bind_param("i", $makh);
    $stmt_taogh->execute();
    $magh = $connect->insert_id;
    $stmt_taogh->close();
} else {
    $row_giohang = $result_giohang->fetch_assoc();
    $magh = $row_giohang['magh'];
}
$stmt->close();

// Xử lý thêm sản phẩm
if (isset($_GET['masp'])) {
    $masp = (int)$_GET['masp'];
    $soluong = 1;
} elseif ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['masp'])) {
    $masp = (int)$_POST['masp'];
    $soluong = isset($_POST['soluong']) ? intval($_POST['soluong']) : 1;
} else {
    $response['message'] = 'Thiếu mã sản phẩm';
    if ($isAjax) {
        echo json_encode($response);
        exit;
    } else {
        header("Location: /Project1_Product/index.php");
        exit;
    }
}

try {
    // Kiểm tra số lượng hợp lệ
    if ($soluong <= 0) {
        $response['message'] = 'Số lượng sản phẩm phải lớn hơn 0';
        if ($isAjax) {
            echo json_encode($response);
            exit;
        } else {
            $_SESSION['error'] = "Số lượng sản phẩm phải lớn hơn 0.";
            header("Location: " . $_SERVER['HTTP_REFERER']);
            exit;
        }
    }

    // Kiểm tra tồn kho
    $stmt_stock = $connect->prepare("SELECT soluongton FROM sanpham WHERE masp = ?");
    $stmt_stock->bind_param("i", $masp);
    $stmt_stock->execute();
    $result_stock = $stmt_stock->get_result();
    if ($result_stock->num_rows == 0) {
        $response['message'] = 'Sản phẩm không tồn tại';
        if ($isAjax) {
            echo json_encode($response);
            exit;
        } else {
            $_SESSION['error'] = "Sản phẩm không tồn tại.";
            header("Location: " . $_SERVER['HTTP_REFERER']);
            exit;
        }
    }
    $row_stock = $result_stock->fetch_assoc();
    $stmt_stock->close();

    // Kiểm tra số lượng hiện tại trong giỏ hàng
    $stmt_check = $connect->prepare("SELECT soluong FROM chitietgiohang WHERE magh = ? AND masp = ?");
    $stmt_check->bind_param("ii", $magh, $masp);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    $current_quantity = 0;
    if ($result_check->num_rows > 0) {
        $row = $result_check->fetch_assoc();
        $current_quantity = $row['soluong'];
    }
    $stmt_check->close();

    $total_quantity = $current_quantity + $soluong;
    if ($total_quantity > $row_stock['soluongton']) {
        $response['message'] = "Số lượng sản phẩm vượt quá tồn kho. Chỉ còn {$row_stock['soluongton']} sản phẩm.";
        if ($isAjax) {
            echo json_encode($response);
            exit;
        } else {
            $_SESSION['error'] = "Số lượng sản phẩm vượt quá tồn kho. Chỉ còn {$row_stock['soluongton']} sản phẩm.";
            header("Location: " . $_SERVER['HTTP_REFERER']);
            exit;
        }
    }

    // Sử dụng INSERT ... ON DUPLICATE KEY UPDATE để tối ưu
    $stmt_upsert = $connect->prepare("
        INSERT INTO chitietgiohang (magh, masp, soluong) 
        VALUES (?, ?, ?) 
        ON DUPLICATE KEY UPDATE soluong = soluong + ?
    ");
    $stmt_upsert->bind_param("iiii", $magh, $masp, $soluong, $soluong);
    if (!$stmt_upsert->execute()) {
        $response['message'] = 'Lỗi khi thêm sản phẩm: ' . $connect->error;
        if ($isAjax) {
            echo json_encode($response);
            exit;
        } else {
            $_SESSION['error'] = "Lỗi khi thêm sản phẩm: " . $connect->error;
            header("Location: " . $_SERVER['HTTP_REFERER']);
            exit;
        }
    }
    $stmt_upsert->close();

    // Cập nhật tổng số lượng và tổng tiền
    $stmt_update_gh = $connect->prepare("
        UPDATE giohang 
        SET tongsl = (SELECT SUM(soluong) FROM chitietgiohang WHERE magh = ?), 
            tongtien = (
                SELECT SUM(ct.soluong * sp.giaban) 
                FROM chitietgiohang ct 
                JOIN sanpham sp ON ct.masp = sp.masp 
                WHERE ct.magh = ?
            )
        WHERE magh = ?
    ");
    $stmt_update_gh->bind_param("iii", $magh, $magh, $magh);
    $stmt_update_gh->execute();
    $stmt_update_gh->close();

    $response['success'] = true;
    $response['message'] = 'Sản phẩm đã được thêm vào giỏ hàng';
    if ($isAjax) {
        echo json_encode($response);
        exit;
    } else {
        $_SESSION['success'] = "Sản phẩm đã được thêm vào giỏ hàng.";
        $redirect = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '/Project1_Product/cart/cart.php';
        header("Location: " . $redirect);
        exit;
    }
} catch (Exception $e) {
    $response['message'] = 'Có lỗi xảy ra: ' . $e->getMessage();
    if ($isAjax) {
        echo json_encode($response);
        exit;
    } else {
        $_SESSION['error'] = "Có lỗi xảy ra: " . $e->getMessage();
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit;
    }
}

$connect->close();
?>
