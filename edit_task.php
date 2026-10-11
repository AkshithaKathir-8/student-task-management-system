
<?php
session_start();
require_once "db.php";

$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id) {
    $_SESSION["message"] = "Invalid assignment ID.";
    header("Location: index.php");
    exit;
}

$stmt = $conn->prepare("SELECT * FROM tasks WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();
$task = $result->fetch_assoc();
$stmt->close();

if (!$task) {
    $_SESSION["message"] = "Assignment not found.";
    header("Location: index.php");
    exit;
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

$message = $_SESSION["message"] ?? "";
unset($_SESSION["message"]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Assignment | Student Task Manager</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <main class="main-content">
        <header class="topbar">
            <div>
                <h1>Edit Assignment</h1>
                <p>Update your assignment details below.</p>
            </div>
            <a href="index.php" class="btn-secondary">Back to Dashboard</a>
        </header>

        <?php if ($message !== ""): ?>
            <div class="alert">
                <?= e($message) ?>
            </div>
        <?php endif; ?>

        <section class="form-section" id="task-form">
            <h2>Assignment Details</h2>

            <form action="save_task.php" method="POST" id="assignmentForm">
                <input type="hidden" name="id"
                       value="<?= e($task["id"]) ?>">

                <div class="form-grid">
                    <div class="form-group">
                        <label for="title">Assignment Title *</label>
                        <input type="text" id="title" name="title"
                               maxlength="150" required
                               value="<?= e($task["title"]) ?>">
                    </div>

                    <div class="form-group">
                        <label for="subject">Subject *</label>
                        <input type="text" id="subject" name="subject"
                               maxlength="100" required
                               value="<?= e($task["subject"]) ?>">
                    </div>

                    <div class="form-group full-width">
                        <label for="description">Description</label>
                        <textarea id="description" name="description"
                                  rows="4"><?= e($task["description"]) ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="due_date">Due Date *</label>
                        <input type="date" id="due_date" name="due_date"
                               required value="<?= e($task["due_date"]) ?>">
                    </div>

                    <div class="form-group">
                        <label for="priority">Priority</label>
                        <select id="priority" name="priority">
                            <?php foreach (["Low", "Medium", "High"] as $p): ?>
                                <option value="<?= e($p) ?>"
                                    <?= $task["priority"] === $p ? "selected" : "" ?>>
                                    <?= e($p) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status">
                            <?php foreach (["Pending", "In Progress", "Completed"] as $s): ?>
                                <option value="<?= e($s) ?>"
                                    <?= $task["status"] === $s ? "selected" : "" ?>>
                                    <?= e($s) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-primary">
                        Save Changes
                    </button>
                    <a href="index.php" class="btn-secondary">Cancel</a>
                </div>
            </form>
        </section>
    </main>
</body>
</html>