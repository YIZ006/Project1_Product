<?php 
// session_start();
include $_SERVER['DOCUMENT_ROOT'] . '/Project1_Product/connect.php';
include(__DIR__ .'/dashboard.php');

$stt = 1;
// Lấy giá trị tìm kiếm từ URL
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Truy vấn lấy danh sách sinh viên cùng với các khóa học đã đăng ký (với tìm kiếm)
$query_enrollments = "SELECT `admin_log`.`log_id`, `admin_log`.`admin_id`, `admin_log`.`hanhdong`, `admin_log`.`thoigian`
FROM `admin_log`
WHERE 
    `admin_log`.`admin_id` LIKE '%$search%' OR 
    `admin_log`.`hanhdong` LIKE '%$search%' OR 
    `admin_log`.`thoigian` LIKE '%$search%' 
ORDER BY `admin_log`.`log_id` DESC";  // Sắp xếp mới nhất lên đầu

$result_enrollments = mysqli_query($connect, $query_enrollments);
?>

<table border="1">
    <tr>
        <th>ID</th>
        <th>ID ADMIN</th>
        <th>ACTION</th>
        <th>TIME</th>
    </tr>
    <?php while ($row = mysqli_fetch_assoc($result_enrollments)): ?>
        <tr>
            <td><?= $stt++ ?></td>
            <td><?= $row['admin_id'] ?></td>
            <td><?= $row['hanhdong'] ?></td>
            <td><?= $row['thoigian'] ?></td>
        
        </tr>
    <?php endwhile; ?>
    <?php if (mysqli_num_rows($result_enrollments) == 0) {
        echo "<tr><td colspan='6'>Không có thông tin nào</td></tr>";
    }
    ?>
</table>

