<?php
$host = "Localhost";
$username = "root";
$password = "";
$database = "wed_megatech";

$connect  = mysqli_connect($host,$username,$password,$database);
if($connect){
    mysqli_set_charset($connect,"utf8");
    // echo "Kết nối thành công";
}else
{
    die("failed to connect database".mysqli_connect_error());
}
?>