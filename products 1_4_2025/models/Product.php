<?php
require_once "config/database.php";

class Product{
    public $id;
    public $name;
    public $price;
    public $image;
    public $created_at;
    public $db;

    //Hàm khởi tạo và kết nối CSDL

    public function __construct($id = null, $name = "", $price = 0, $image = "", $created_at = "")
    {
        
        $this->id = $id;
        $this->name = $name;
        $this->price = $price;
        $this->image = $image;
        $this->created_at = $created_at;
        $this->db = (new Database())->connect;
      
    }

    //Lấy danh sách sản phẩm và trả về danh sách các object Product
    public function getAllProduct(){
        $sql = "SELECT * FROM products ORDER BY created_at DESC";
        $result = $this->db->query($sql);
        $products = [];
        while ($row = $result->fetch_assoc()){
            $product[] = new Product($row['id'], $row['name'], $row['price'], $row['image'], $row['created_at']);
           
        }
        return $products;
    }
}
?>