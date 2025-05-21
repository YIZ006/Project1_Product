<?php 
include __DIR__ . "view/layout.php";?>
<h2>Danh Sách Sản Phẩm</h2>
<div class="product-list">
    <?php foreach($products as $product):?>
        <div class="product">
            <img src="public/images/<?= $product->image ?> " width="100" alt="">
            <h3><?= htmlspecialchars($product->name) ?></h3>
            <p>Giá:<?= number_format($product->price,0,',','.')?> đ</p>
        </div>
        <?php endforeach;?>
</div>