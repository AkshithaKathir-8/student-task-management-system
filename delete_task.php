<?php
session_start();
require_once "db.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php");
    exit;
}

$id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);

if (!$id) {
    $_SESSION["message"] = "Invalid task ID.";
    header("Location: index.php");
    exit;
}

$stmt = $conn->prepare("DELETE FROM tasks WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

$_SESSION["message"] = $stmt->affected_rows > 0
    ? "Assignment deleted successfully!"
    : "Assignment not found.";

$stmt->close();
header("Location: index.php");
exit;
?>