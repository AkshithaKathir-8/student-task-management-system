<?php
session_start();
require_once "db.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php");
    exit;
}

$title = trim($_POST["title"] ?? "");
$subject = trim($_POST["subject"] ?? "");
$description = trim($_POST["description"] ?? "");
$due_date = $_POST["due_date"] ?? "";
$priority = $_POST["priority"] ?? "Medium";
$status = $_POST["status"] ?? "Pending";

$validPriorities = ["Low", "Medium", "High"];
$validStatuses = ["Pending", "In Progress", "Completed"];

$date = DateTime::createFromFormat("!Y-m-d", $due_date);

if (
    $title === "" ||
    $subject === "" ||
    strlen($title) > 150 ||
    strlen($subject) > 100 ||
    !in_array($priority, $validPriorities, true) ||
    !in_array($status, $validStatuses, true) ||
    !$date ||
    $date->format("Y-m-d") !== $due_date
) {
    $_SESSION["message"] = "Invalid input. Please check the assignment details.";
    header("Location: index.php#task-form");
    exit;
}

$stmt = $conn->prepare(
    "INSERT INTO tasks (title, subject, description, due_date, priority, status)
     VALUES (?, ?, ?, ?, ?, ?)"
);

$stmt->bind_param(
    "ssssss",
    $title,
    $subject,
    $description,
    $due_date,
    $priority,
    $status
);

if ($stmt->execute()) {
    $_SESSION["message"] = "Assignment added successfully!";
} else {
    $_SESSION["message"] = "Unable to add assignment. Please try again.";
}

$stmt->close();
header("Location: index.php");
exit;
?>