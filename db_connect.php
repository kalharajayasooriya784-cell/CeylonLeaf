<?php
$servername = "localhost";
$username = "root"; // Your phpMyAdmin username
$password = ""; // Your phpMyAdmin password
$dbname = "ceylonleaf_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>