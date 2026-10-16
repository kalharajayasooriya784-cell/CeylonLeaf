<?php
$host = "sql210.infinityfree.com";
$username = "if0_43190492";
$password = "Sadeepa123D";
$database = "if0_43190492_ceylonleaf_db";
$port = 3306;

$conn = mysqli_connect($host, $username, $password, $database, $port);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>