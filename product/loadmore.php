<?php
include(__DIR__ . '/../connect.php');

if ($connect->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Kết nối thất bại']));
}

$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;

$sql_loai = "SELECT * FROM loaisanpham LIMIT ?, ?";
$stmt_loai = $connect->prepare($sql_loai);
$stmt_loai->bind_param("ii", $offset, $limit);
$stmt_loai->execute();
$result_loai = $stmt_loai->get_result();

$categories = [];
while ($loai = $result_loai->fetch_assoc()) {
    $maloai = $loai['maloai'];
    $sql_sp = "
        SELECT masp, tensp, giaban, soluongton, hinhanh, mota, giamgia
        FROM sanpham
        WHERE maloai = ?
    ";
    $stmt_sp = $connect->prepare($sql_sp);
    $stmt_sp->bind_param("i", $maloai);
    $stmt_sp->execute();
    $result_sp = $stmt_sp->get_result();

    $products = [];
    while ($row = $result_sp->fetch_assoc()) {
        $products[] = $row;
    }
    $stmt_sp->close();

    $categories[] = [
        'maloai' => $loai['maloai'],
        'tenloai' => htmlspecialchars($loai['tenloai']),
        'products' => $products
    ];
}
$stmt_loai->close();
$connect->close();

echo json_encode(['success' => true, 'categories' => $categories]);
?>
