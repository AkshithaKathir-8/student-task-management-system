<?php
session_start();
require_once "db.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php");
    exit;
}

$id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);
$status = $_POST["status"] ?? "";

$validStatuses = ["Pending", "In Progress", "Completed"];

if (!$id || !in_array($status, $validStatuses, true)) {
    $_SESSION["message"] = "Invalid task or status.";
    header("Location: index.php");
    exit;
}

$stmt = $conn->prepare("UPDATE tasks SET status = ? WHERE id = ?");
$stmt->bind_param("si", $status, $id);
$stmt->execute();

$_SESSION["message"] = $stmt->affected_rows >= 0
    ? "Task status updated successfully!"
    : "Unable to update task status.";

$stmt->close();
header("Location: index.php");
exit;
?>