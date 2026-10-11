
<?php
session_start();
require_once "db.php";

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

$message = $_SESSION["study_message"] ?? "";
unset($_SESSION["study_message"]);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";

    if ($action === "add") {
        $title = trim($_POST["title"] ?? "");
        $subject = trim($_POST["subject"] ?? "");
        $study_date = $_POST["study_date"] ?? "";
        $start_time = trim($_POST["start_time"] ?? "");
        $duration = filter_input(INPUT_POST, "duration", FILTER_VALIDATE_INT);
        $notes = trim($_POST["notes"] ?? "");

        $date = DateTime::createFromFormat("!Y-m-d", $study_date);
        $timeValid = $start_time === "" ||
            (bool) preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $start_time);

        if (
            $title === "" || strlen($title) > 150 ||
            $subject === "" || strlen($subject) > 100 ||
            !$date || $date->format("Y-m-d") !== $study_date ||
            !$timeValid ||
            $duration === false || $duration < 1 || $duration > 1440
        ) {
            $_SESSION["study_message"] = "Please enter valid study session details.";
        } else {
            $stmt = $conn->prepare(
                "INSERT INTO study_sessions
                (title, subject, study_date, start_time, duration_minutes, notes)
                VALUES (?, ?, ?, NULLIF(?, ''), ?, ?)"
            );
            $stmt->bind_param(
                "ssssis",
                $title, $subject, $study_date,
                $start_time, $duration, $notes
            );

            $_SESSION["study_message"] = $stmt->execute()
                ? "Study session added successfully!"
                : "Unable to add the session.";

            $stmt->close();
        }

        header("Location: study_planner.php");
        exit;
    }

    if ($action === "toggle") {
        $id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);
        $status = $_POST["status"] ?? "";

        if ($id && in_array($status, ["Planned", "Completed"], true)) {
            $stmt = $conn->prepare(
                "UPDATE study_sessions SET status = ? WHERE id = ?"
            );
            $stmt->bind_param("si", $status, $id);
            $stmt->execute();
            $_SESSION["study_message"] = "Study session updated.";
            $stmt->close();
        }

        header("Location: study_planner.php");
        exit;
    }

    if ($action === "delete") {
        $id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);

        if ($id) {
            $stmt = $conn->prepare("DELETE FROM study_sessions WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $_SESSION["study_message"] = $stmt->affected_rows > 0
                ? "Study session deleted."
                : "Session not found.";
            $stmt->close();
        }

        header("Location: study_planner.php");
        exit;
    }
}

$today = date("Y-m-d");

$total = (int) $conn->query(
    "SELECT COUNT(*) AS total FROM study_sessions"
)->fetch_assoc()["total"];

$completed = (int) $conn->query(
    "SELECT COUNT(*) AS total FROM study_sessions WHERE status = 'Completed'"
)->fetch_assoc()["total"];

$upcomingStmt = $conn->prepare(
    "SELECT COUNT(*) AS total FROM study_sessions
     WHERE study_date >= ? AND status = 'Planned'"
);
$upcomingStmt->bind_param("s", $today);
$upcomingStmt->execute();
$upcoming = (int) $upcomingStmt->get_result()->fetch_assoc()["total"];
$upcomingStmt->close();


$sessions = $conn->query(
    "SELECT * FROM study_sessions
     ORDER BY study_date ASC, start_time ASC, id DESC"
);

// Calendar month navigation
$requestedMonth = $_GET["month"] ?? date("Y-m");

if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $requestedMonth)) {
    $requestedMonth = date("Y-m");
}

$firstDay = DateTimeImmutable::createFromFormat(
    "!Y-m-d", $requestedMonth . "-01"
);

if (!$firstDay || $firstDay->format("Y-m") !== $requestedMonth) {
    $firstDay = new DateTimeImmutable("first day of this month");
}

$monthStart = $firstDay->format("Y-m-d");
$monthEnd = $firstDay->modify("last day of this month")->format("Y-m-d");
$daysInMonth = (int) $firstDay->format("t");
$startingWeekday = (int) $firstDay->format("N");
$monthLabel = $firstDay->format("F Y");
$prevMonth = $firstDay->modify("-1 month")->format("Y-m");
$nextMonth = $firstDay->modify("+1 month")->format("Y-m");

$calendarEvents = [];

// Fetch assignment deadlines
$stmt = $conn->prepare(
    "SELECT id, title, due_date, status
     FROM tasks
     WHERE due_date BETWEEN ? AND ?"
);
$stmt->bind_param("ss", $monthStart, $monthEnd);
$stmt->execute();
$result = $stmt->get_result();

while ($event = $result->fetch_assoc()) {
    $calendarEvents[$event["due_date"]][] = [
        "title" => $event["title"],
        "type" => "assignment",
        "status" => $event["status"]
    ];
}
$stmt->close();

// Fetch study sessions
$stmt = $conn->prepare(
    "SELECT id, title, study_date, status
     FROM study_sessions
     WHERE study_date BETWEEN ? AND ?"
);
$stmt->bind_param("ss", $monthStart, $monthEnd);
$stmt->execute();
$result = $stmt->get_result();

while ($event = $result->fetch_assoc()) {
    $calendarEvents[$event["study_date"]][] = [
        "title" => $event["title"],
        "type" => "study",
        "status" => $event["status"]
    ];
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Study Planner | Student Task Manager</title>
    <link rel="stylesheet" href="style.css">
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
<a class="nav-link" href="index.php">▦ &nbsp; Dashboard</a>
<a class="nav-link" href="index.php#task-list">☷ &nbsp; My Assignments</a>
<a class="nav-link active" href="study_planner.php">◷ &nbsp; Study Planner</a>
<a class="nav-link" href="#academic-calendar">▦ &nbsp; Academic Calendar</a>

        <div class="sidebar-bottom">
            <div class="tip">
                <span>Study tip</span>
                <p>Break your study goals into focused sessions and track your progress.</p>
            </div>
            <small>Student Task Manager</small>
        </div>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <div>
                <p class="eyebrow">PLAN YOUR LEARNING</p>
                <h1>Study Planner</h1>
                <p class="muted">Organize study sessions and keep track of your learning goals.</p>
            </div>
            <a href="index.php" class="secondary-button">← Dashboard</a>
        </header>

        <?php if ($message !== ""): ?>
            <div class="notice"><?= e($message) ?></div>
        <?php endif; ?>

        <section class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon purple">▤</div>
                <p>Total Sessions</p>
                <h2><?= $total ?></h2>
                <small>All planned study sessions</small>
            </div>
            <div class="stat-card">
                <div class="stat-icon green">✓</div>
                <p>Completed</p>
                <h2><?= $completed ?></h2>
                <small>Sessions finished</small>
            </div>
            <div class="stat-card">
                <div class="stat-icon blue">◷</div>
                <p>Upcoming</p>
                <h2><?= $upcoming ?></h2>
                <small>Planned sessions from today</small>
            </div>
            <div class="stat-card">
                <div class="stat-icon orange">%</div>
                <p>Completion Rate</p>
                <h2><?= $total ? round($completed / $total * 100) : 0 ?>%</h2>
                <small>Based on all sessions</small>
            </div>
        </section>

        <section class="panel" id="add-session">
            <div class="section-heading">
                <div>
                    <h2>Create Study Session</h2>
                    <p class="muted">Add a study goal to your personal schedule.</p>
                </div>
            </div>

            <form method="POST" action="study_planner.php" class="task-form">
                <input type="hidden" name="action" value="add">

                <div class="form-group">
                    <label for="title">Session Title *</label>
                    <input id="title" name="title" maxlength="150"
                           placeholder="e.g. Revise SQL joins" required>
                </div>

                <div class="form-group">
                    <label for="subject">Subject *</label>
                    <input id="subject" name="subject" maxlength="100"
                           placeholder="e.g. Database Management" required>
                </div>

                <div class="form-group">
                    <label for="study_date">Study Date *</label>
                    <input id="study_date" name="study_date" type="date"
                           min="<?= e($today) ?>" value="<?= e($today) ?>" required>
                </div>

                <div class="form-group">
                    <label for="start_time">Start Time (optional)</label>
                    <input id="start_time" name="start_time" type="time">
                </div>

                <div class="form-group">
                    <label for="duration">Duration (minutes) *</label>
                    <input id="duration" name="duration" type="number"
                           min="1" max="1440" value="60" required>
                </div>

                <div class="form-group full-width">
                    <label for="notes">Study Notes (optional)</label>
                    <textarea id="notes" name="notes" rows="3"
                              placeholder="Topics to cover or goals for this session"></textarea>
                </div>

                <div class="form-actions full-width">
                    <button type="submit" class="primary-button">+ Add Study Session</button>
                </div>
            </form>
        </section>

        <section class="panel">
            
<section class="panel calendar-panel" id="academic-calendar">
        <div class="section-heading">
        <div>
            <h2>Academic Calendar</h2>
            <p class="muted">Assignment deadlines and study sessions in one view.</p>
        </div>
    </div>

    <div class="calendar-navigation">
        <a class="secondary-button"
           href="study_planner.php?month=<?= e($prevMonth) ?>">← Previous</a>

        <h3><?= e($monthLabel) ?></h3>

        <a class="secondary-button"
           href="study_planner.php?month=<?= e($nextMonth) ?>">Next →</a>
    </div>

    <div class="calendar-grid">
        <?php foreach (["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"] as $weekday): ?>
            <div class="calendar-weekday"><?= e($weekday) ?></div>
        <?php endforeach; ?>

        <?php for ($blank = 1; $blank < $startingWeekday; $blank++): ?>
            <div class="calendar-day calendar-empty"></div>
        <?php endfor; ?>

        <?php for ($day = 1; $day <= $daysInMonth; $day++): ?>
            <?php
                $dateKey = $firstDay->setDate(
                    (int) $firstDay->format("Y"),
                    (int) $firstDay->format("m"),
                    $day
                )->format("Y-m-d");

                $isToday = $dateKey === date("Y-m-d");
            ?>

            <div class="calendar-day <?= $isToday ? "calendar-today" : "" ?>">
                <span class="calendar-date"><?= $day ?></span>

                <?php foreach (($calendarEvents[$dateKey] ?? []) as $event): ?>
                    <div class="calendar-event <?= $event["type"] === "assignment"
                        ? "event-assignment" : "event-study" ?>"
                        title="<?= e($event["title"]) ?>">
                        <span class="event-type">
                            <?= $event["type"] === "assignment" ? "A" : "S" ?>
                        </span>
                        <?= e($event["title"]) ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endfor; ?>
    </div>

    <div class="calendar-legend">
        <span><i class="legend-dot assignment-dot"></i> Assignment deadline</span>
        <span><i class="legend-dot study-dot"></i> Study session</span>
    </div>
</section>
            <div class="section-heading">
                <div>
                    <h2>Your Study Schedule</h2>
                    <p class="muted">Manage your planned and completed sessions.</p>
                </div>
                <span class="result-count"><?= $total ?> sessions</span>
            </div>

            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Study Session</th>
                            <th>Date & Time</th>
                            <th>Duration</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($sessions->num_rows === 0): ?>
                        <tr>
                            <td colspan="5">
                                <div class="empty-state">
                                    <div>◷</div>
                                    <strong>No study sessions yet</strong>
                                    <p>Create your first session using the form above.</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php while ($session = $sessions->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <strong><?= e($session["title"]) ?></strong>
                                    <span class="subject"><?= e($session["subject"]) ?></span>
                                    <?php if ($session["notes"] !== ""): ?>
                                        <span class="description"><?= e($session["notes"]) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= e(date("d M Y", strtotime($session["study_date"]))) ?>
                                    <br>
                                    <span class="subject">
                                        <?= $session["start_time"]
                                            ? e(date("h:i A", strtotime($session["start_time"])))
                                            : "Time not set" ?>
                                    </span>
                                </td>
                                <td><?= (int) $session["duration_minutes"] ?> min</td>
                                <td>
                                    <span class="badge <?= $session["status"] === "Completed"
                                        ? "priority-low" : "priority-medium" ?>">
                                        <?= e($session["status"]) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="actions">
                                        <form method="POST" action="study_planner.php">
                                            <input type="hidden" name="action" value="toggle">
                                            <input type="hidden" name="id"
                                                   value="<?= (int) $session["id"] ?>">
                                            <input type="hidden" name="status"
                                                   value="<?= $session["status"] === "Completed"
                                                       ? "Planned" : "Completed" ?>">
                                            <button class="edit-link" type="submit">
                                                <?= $session["status"] === "Completed"
                                                    ? "Undo" : "Complete" ?>
                                            </button>
                                        </form>

                                        <form method="POST" action="study_planner.php"
                                              onsubmit="return confirm('Delete this study session?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id"
                                                   value="<?= (int) $session["id"] ?>">
                                            <button class="delete-button" type="submit">Delete</button>
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

        <footer>Student Task and Assignment Management System</footer>
    </main>
</div>
</body>
</html>