<?php 
$username = "p598801_test";
$pass ="Admin@786";
$server = "localhost";
$db = "p598801_driver_test";

$conn = mysqli_connect($server,$username,$pass,$db);

if(!$conn){
echo "Connection Error";
}

?>