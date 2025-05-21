<?php
include __DIR__ . "/../models/Product.php";

class ProductController{
    private $productModel;

    public function __construct()
    {
        $this->productModel = new Product();
    }
    public function index(){
        $products = $this->productModel->getAllProduct();
        include __DIR__ . "/../views/product/list.php";
    }
}
?>