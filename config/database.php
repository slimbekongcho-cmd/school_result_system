<?php
$host = "localhost";
$port="3307";
$user = "root";
$password = "";
$database = "uniresult_db";

$conn = new mysqli($host, $user, $password, $database,$port);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Connection successful
?>