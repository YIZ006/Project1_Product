<?php
class Product
{
    private $host = "localhost";
    private $db_name = "product_db";
    private $username = "root";
    private $password = "";
    public $connect;

    public function __construct()
    {
        $this->connect = new mysqli($this->host, $this->username, $this->password, $this->db_name);
        if ($this->connect->connect_error) {
            die("Failed to connect to MySQL: " . $this->connect->connect_error);
        } else {
            echo "Connected";
        }
    }
}
