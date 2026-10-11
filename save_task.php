
<?php
session_start();
require_once "db.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php");
    exit;
}

$id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);
$title = trim($_POST["title"] ?? "");
$subject = trim($_POST["subject"] ?? "");
$description = trim($_POST["description"] ?? "");
$due_date = $_POST["due_date"] ?? "";
$priority = $_POST["priority"] ?? "";
$status = $_POST["status"] ?? "";

$validPriorities = ["Low", "Medium", "High"];
$validStatuses = ["Pending", "In Progress", "Completed"];

$date = DateTime::createFromFormat("!Y-m-d", $due_date);

if (
    !$id ||
    $title === "" ||
    $subject === "" ||
    strlen($title) > 150 ||
    strlen($subject) > 100 ||
    !in_array($priority, $validPriorities, true) ||
    !in_array($status, $validStatuses, true) ||
    !$date ||
    $date->format("Y-m-d") !== $due_date
) {
    $_SESSION["message"] = "Invalid details. Please check the form.";
    header("Location: index.php");
    exit;
}

$stmt = $conn->prepare(
    "UPDATE tasks
     SET title = ?, subject = ?, description = ?,
         due_date = ?, priority = ?, status = ?
     WHERE id = ?"
);

$stmt->bind_param(
    "ssssssi",
    $title,
    $subject,
    $description,
    $due_date,
    $priority,
    $status,
    $id
);

if ($stmt->execute()) {
    $_SESSION["message"] = $stmt->affected_rows >= 0
        ? "Assignment updated successfully!"
        : "Unable to update assignment.";
} else {
    $_SESSION["message"] = "Unable to update assignment.";
}

$stmt->close();

header("Location: index.php");
exit;
?>