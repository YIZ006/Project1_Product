<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <style>
        .product-list{
            display: flex;
            gap: 20px;
        }
        .product{
            border: 1px solid #ccc;
            padding: 10px;
            width: 200px;
            text-align: center;
        }
    </style>
</head>
<body>
    <header>
        <h1>Quản Lý Sản Phẩm</h1>
        <nav>
            <a href="index.php">Trang chủ</a>
        </nav>
    </header>
    <main>
        <?php include_once $view;?>
    </main>
</body>
</html>