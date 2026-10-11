<?php
$host = "localhost";
$user = "root";
$password = "YOUR_DATABASE_PASSWORD";
$database = "student_task_manager";
$port = 3306;

$conn = new mysqli($host, $username, $password, $database, $port);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
?>