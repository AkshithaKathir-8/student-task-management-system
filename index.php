<?php
session_start();
require_once "db.php";

$stats = [
    "total" => 0,
    "pending" => 0,
    "progress" => 0,
    "completed" => 0,
    "overdue" => 0
];

$result = $conn->query("SELECT COUNT(*) AS total FROM tasks");
$stats["total"] = (int)$result->fetch_assoc()["total"];

$result = $conn->query("SELECT COUNT(*) AS total FROM tasks WHERE status='Pending'");
$stats["pending"] = (int)$result->fetch_assoc()["total"];

$result = $conn->query("SELECT COUNT(*) AS total FROM tasks WHERE status='In Progress'");
$stats["progress"] = (int)$result->fetch_assoc()["total"];

$result = $conn->query("SELECT COUNT(*) AS total FROM tasks WHERE status='Completed'");
$stats["completed"] = (int)$result->fetch_assoc()["total"];

$result = $conn->query(
    "SELECT COUNT(*) AS total FROM tasks
     WHERE due_date < CURDATE() AND status != 'Completed'"
);
$stats["overdue"] = (int)$result->fetch_assoc()["total"];

$search = trim($_GET["search"] ?? "");
$status = $_GET["status"] ?? "";
$priority = $_GET["priority"] ?? "";

$sql = "SELECT * FROM tasks WHERE 1=1";
$params = [];
$types = "";

if ($search !== "") {
    $sql .= " AND (title LIKE ? OR subject LIKE ?)";
    $term = "%".$search."%";
    $params[] = $term;
    $params[] = $term;
    $types .= "ss";
}

if (in_array($status, ["Pending", "In Progress", "Completed"], true)) {
    $sql .= " AND status = ?";
    $params[] = $status;
    $types .= "s";
}

if (in_array($priority, ["Low", "Medium", "High"], true)) {
    $sql .= " AND priority = ?";
    $params[] = $priority;
    $types .= "s";
}

$sql .= " ORDER BY
    CASE WHEN status = 'Completed' THEN 1 ELSE 0 END,
    due_date ASC, id DESC";

$stmt = $conn->prepare($sql);

if ($params) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$tasks = $stmt->get_result();

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
    <title>Student Task Manager</title>
    <link rel="stylesheet" href="style.css">
    <script src="script.js" defer></script>
</head>
<body>
<div class="app">
    <aside class="sidebar">
        <div class="brand">
            <div class="brand-icon">S</div>
            <div>
                <h2>StudyFlow</h2>
                <small>STUDENT WORKSPACE</small>
            </div>
        </div>

        
<p class="menu-label">WORKSPACE</p>
<a class="nav-link active" href="index.php">▦ &nbsp; Dashboard</a>
<a class="nav-link" href="#task-list">☷ &nbsp; My Assignments</a>
<a class="nav-link" href="study_planner.php">◷ &nbsp; Study Planner</a>
<a class="nav-link" href="study_planner.php#academic-calendar">▦ &nbsp; Academic Calendar</a>

        <div class="sidebar-bottom">
            <div class="tip">
                <span>✦ Study tip</span>
                <p>Small progress every day leads to big achievements.</p>
            </div>
            <small>Student Task Manager · 2026</small>
        </div>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <div>
                <p class="eyebrow">YOUR PERSONAL WORKSPACE</p>
                <h1>Dashboard</h1>
                <p class="muted">Organize your assignments and stay on track.</p>
            </div>
            <a href="#task-form" class="primary-button">＋ Add Assignment</a>
        </header>

        <?php if ($message !== ""): ?>
            <div class="notice"><?= e($message) ?></div>
        <?php endif; ?>

        <section class="stats-grid">
            <article class="stat-card">
                <span class="stat-icon purple">▦</span>
                <p>Total Assignments</p>
                <h2><?= $stats["total"] ?></h2>
                <small>All your tasks</small>
            </article>
            <article class="stat-card">
                <span class="stat-icon orange">◷</span>
                <p>Pending</p>
                <h2><?= $stats["pending"] ?></h2>
                <small>Waiting to be done</small>
            </article>
            <article class="stat-card">
                <span class="stat-icon blue">↗</span>
                <p>In Progress</p>
                <h2><?= $stats["progress"] ?></h2>
                <small>Currently working on</small>
            </article>
            <article class="stat-card">
                <span class="stat-icon green">✓</span>
                <p>Completed</p>
                <h2><?= $stats["completed"] ?></h2>
                <small>Successfully finished</small>
            </article>
        </section>

        <section class="overdue-banner">
            <div>
                <strong>Overdue assignments</strong>
                <p>Keep an eye on tasks whose deadlines have passed.</p>
            </div>
            <span class="overdue-count"><?= $stats["overdue"] ?></span>
        </section>

        <section class="panel" id="task-form">
            <div class="section-heading">
                <div>
                    <h2>Add New Assignment</h2>
                    <p class="muted">Fill in the details to create a task.</p>
                </div>
            </div>

            <form action="add_task.php" method="POST" class="task-form" id="assignmentForm">
                <div class="form-group">
                    <label for="title">Assignment Title *</label>
                    <input type="text" id="title" name="title" maxlength="150"
                           placeholder="e.g. Database Management Assignment" required>
                </div>

                <div class="form-group">
                    <label for="subject">Subject *</label>
                    <input type="text" id="subject" name="subject" maxlength="100"
                           placeholder="e.g. Database Management Systems" required>
                </div>

                <div class="form-group full-width">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="3"
                              placeholder="Add assignment details..."></textarea>
                </div>

                <div class="form-group">
                    <label for="due_date">Due Date *</label>
                    <input type="date" id="due_date" name="due_date" required>
                </div>

                <div class="form-group">
                    <label for="priority">Priority</label>
                    <select id="priority" name="priority">
                        <option value="Medium">Medium</option>
                        <option value="High">High</option>
                        <option value="Low">Low</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="task_status">Status</label>
                    <select id="task_status" name="status">
                        <option value="Pending">Pending</option>
                        <option value="In Progress">In Progress</option>
                        <option value="Completed">Completed</option>
                    </select>
                </div>

                <div class="form-actions full-width">
                    <button type="reset" class="secondary-button">Clear</button>
                    <button type="submit" class="primary-button">Save Assignment</button>
                </div>
            </form>
        </section>

        <section class="panel" id="task-list">
            <div class="section-heading">
                <div>
                    <h2>My Assignments</h2>
                    <p class="muted">View and manage all your tasks.</p>
                </div>
                <span class="result-count"><?= $tasks->num_rows ?> tasks</span>
            </div>

            <form method="GET" action="index.php" class="filters">
                <input type="search" name="search" placeholder="Search title or subject..."
                       value="<?= e($search) ?>">

                <select name="status">
                    <option value="">All statuses</option>
                    <?php foreach (["Pending", "In Progress", "Completed"] as $option): ?>
                        <option value="<?= e($option) ?>" <?= $status === $option ? "selected" : "" ?>>
                            <?= e($option) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <select name="priority">
                    <option value="">All priorities</option>
                    <?php foreach (["High", "Medium", "Low"] as $option): ?>
                        <option value="<?= e($option) ?>" <?= $priority === $option ? "selected" : "" ?>>
                            <?= e($option) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <button type="submit" class="secondary-button">Apply</button>
                <a href="index.php" class="clear-link">Reset</a>
            </form>

            <div class="table-wrapper">
                <table>
                    <thead>
                    <tr>
                        <th>Assignment</th>
                        <th>Due Date</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if ($tasks->num_rows === 0): ?>
                        <tr>
                            <td colspan="5" class="empty-state">
                                <div>☷</div>
                                <strong>No assignments found</strong>
                                <p>Add an assignment above to get started.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php while ($task = $tasks->fetch_assoc()): ?>
                            <?php
                            $isOverdue = $task["due_date"] < date("Y-m-d")
                                && $task["status"] !== "Completed";
                            ?>
                            <tr>
                                <td>
                                    <strong><?= e($task["title"]) ?></strong>
                                    <small class="subject"><?= e($task["subject"]) ?></small>
                                    <?php if ($task["description"] !== ""): ?>
                                        <small class="description"><?= e($task["description"]) ?></small>
                                    <?php endif; ?>
                                    <?php if ($isOverdue): ?>
                                        <small class="overdue-text">Overdue</small>
                                    <?php endif; ?>
                                </td>
                                <td><?= e(date("d M Y", strtotime($task["due_date"]))) ?></td>
                                <td><span class="badge priority-<?= strtolower(e($task["priority"])) ?>">
                                    <?= e($task["priority"]) ?>
                                </span></td>
                                <td>
                                    <form action="update_status.php" method="POST">
                                        <input type="hidden" name="id" value="<?= (int)$task["id"] ?>">
                                        <select name="status" class="status-select"
                                                aria-label="Update status"
                                                onchange="this.form.submit()">
                                            <?php foreach (["Pending", "In Progress", "Completed"] as $option): ?>
                                                <option value="<?= e($option) ?>"
                                                    <?= $task["status"] === $option ? "selected" : "" ?>>
                                                    <?= e($option) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </form>
                                </td>
                                <td>
                                    <div class="actions">
                                        <a class="edit-link"
                                           href="edit_task.php?id=<?= (int)$task["id"] ?>">Edit</a>
                                        <form action="delete_task.php" method="POST"
                                              onsubmit="return confirm('Delete this assignment?');">
                                            <input type="hidden" name="id" value="<?= (int)$task["id"] ?>">
                                            <button type="submit" class="delete-button">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <footer>© 2026 StudyFlow · Student Task and Assignment Management System</footer>
    </main>
</div>
</body>
</html>