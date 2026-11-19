<?php
/**
 * ====================================================================
 * Internship Task Tracker - User Dashboard (dashboard.php)
 * ====================================================================
 * Protected Page: Displays greeting, completion progress, smart reminders,
 * KPI metric cards, and time-filtered task table with categories and deliverables.
 */

// 1. Start Session & Verify Authentication
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// 2. Include Database Connection
require_once 'includes/db.php';

$user_id   = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_role = $_SESSION['user_role'] ?? 'intern';

// 3. Calculate Task Statistics
// A. Total Tasks Count
$sql_total = "SELECT COUNT(*) AS count FROM tasks WHERE user_id = ?";
$stmt_total = mysqli_prepare($conn, $sql_total);
mysqli_stmt_bind_param($stmt_total, "i", $user_id);
mysqli_stmt_execute($stmt_total);
$res_total = mysqli_stmt_get_result($stmt_total);
$total_tasks = mysqli_fetch_assoc($res_total)['count'] ?? 0;
mysqli_stmt_close($stmt_total);

// B. Pending Tasks Count
$sql_pending = "SELECT COUNT(*) AS count FROM tasks WHERE user_id = ? AND status = 'Pending'";
$stmt_pending = mysqli_prepare($conn, $sql_pending);
mysqli_stmt_bind_param($stmt_pending, "i", $user_id);
mysqli_stmt_execute($stmt_pending);
$res_pending = mysqli_stmt_get_result($stmt_pending);
$pending_tasks = mysqli_fetch_assoc($res_pending)['count'] ?? 0;
mysqli_stmt_close($stmt_pending);

// C. In Progress Tasks Count
$sql_inprogress = "SELECT COUNT(*) AS count FROM tasks WHERE user_id = ? AND status = 'In Progress'";
$stmt_inprogress = mysqli_prepare($conn, $sql_inprogress);
mysqli_stmt_bind_param($stmt_inprogress, "i", $user_id);
mysqli_stmt_execute($stmt_inprogress);
$res_inprogress = mysqli_stmt_get_result($stmt_inprogress);
$inprogress_tasks = mysqli_fetch_assoc($res_inprogress)['count'] ?? 0;
mysqli_stmt_close($stmt_inprogress);

// D. Completed Tasks Count
$sql_completed = "SELECT COUNT(*) AS count FROM tasks WHERE user_id = ? AND status = 'Completed'";
$stmt_completed = mysqli_prepare($conn, $sql_completed);
mysqli_stmt_bind_param($stmt_completed, "i", $user_id);
mysqli_stmt_execute($stmt_completed);
$res_completed = mysqli_stmt_get_result($stmt_completed);
$completed_tasks = mysqli_fetch_assoc($res_completed)['count'] ?? 0;
mysqli_stmt_close($stmt_completed);

// E. Smart Reminder Counts: Overdue and Due Today
$sql_overdue = "SELECT COUNT(*) AS count FROM tasks WHERE user_id = ? AND status != 'Completed' AND deadline < CURDATE()";
$stmt_overdue = mysqli_prepare($conn, $sql_overdue);
mysqli_stmt_bind_param($stmt_overdue, "i", $user_id);
mysqli_stmt_execute($stmt_overdue);
$res_overdue = mysqli_stmt_get_result($stmt_overdue);
$overdue_count = mysqli_fetch_assoc($res_overdue)['count'] ?? 0;
mysqli_stmt_close($stmt_overdue);

$sql_today_due = "SELECT COUNT(*) AS count FROM tasks WHERE user_id = ? AND status != 'Completed' AND deadline = CURDATE()";
$stmt_today_due = mysqli_prepare($conn, $sql_today_due);
mysqli_stmt_bind_param($stmt_today_due, "i", $user_id);
mysqli_stmt_execute($stmt_today_due);
$res_today_due = mysqli_stmt_get_result($stmt_today_due);
$today_due_count = mysqli_fetch_assoc($res_today_due)['count'] ?? 0;
mysqli_stmt_close($stmt_today_due);

// F. Calculate Completion Percentage
$progress_percent = ($total_tasks > 0) ? round(($completed_tasks / $total_tasks) * 100) : 0;
$today_date = date('Y-m-d');

// 4. Dashboard Task List Filter: All / Today / This Week / This Month
$filter = $_GET['filter'] ?? 'all';
$allowed_filters = ['all', 'day', 'week', 'month'];
if (!in_array($filter, $allowed_filters, true)) {
    $filter = 'all';
}

$sql_recent = "SELECT id, title, description, category, deadline, priority, status, attachment, submission_link FROM tasks WHERE user_id = ?";
$params = [$user_id];
$types = "i";

if ($filter === 'day') {
    $sql_recent .= " AND deadline = CURDATE()";
} elseif ($filter === 'week') {
    $sql_recent .= " AND deadline BETWEEN DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY) AND DATE_ADD(DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY), INTERVAL 6 DAY)";
} elseif ($filter === 'month') {
    $sql_recent .= " AND YEAR(deadline) = YEAR(CURDATE()) AND MONTH(deadline) = MONTH(CURDATE())";
}

$sql_recent .= " ORDER BY deadline ASC, created_at DESC";
if ($filter === 'all') {
    $sql_recent .= " LIMIT 6";
}

$stmt_recent = mysqli_prepare($conn, $sql_recent);
mysqli_stmt_bind_param($stmt_recent, $types, ...$params);
mysqli_stmt_execute($stmt_recent);
$recent_result = mysqli_stmt_get_result($stmt_recent);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Internship Task Tracker - Dashboard</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <!-- Header & Navigation Bar -->
    <header>
        <nav>
            <div class="logo">
                <h2>📋 TaskTracker</h2>
            </div>
            <ul>
                <?php if ($user_role === 'admin'): ?>
                    <li><a href="admin_dashboard.php" style="color: var(--primary-color); font-weight: 700;">⭐ Manager Panel</a></li>
                <?php endif; ?>
                <li><a href="dashboard.php" class="active">Dashboard</a></li>
                <li><a href="tasks.php">All Tasks</a></li>
                <li><a href="add_task.php">+ Add Task</a></li>
                <li><a href="profile.php">Profile</a></li>
                <li><button id="themeToggleBtn" class="btn-theme-toggle">🌙 Dark Mode</button></li>
                <li><a href="logout.php" class="btn-logout">Logout</a></li>
            </ul>
        </nav>
    </header>

    <!-- Main Dashboard Layout -->
    <main class="dashboard-layout">
        
        <!-- Welcome Greeting Banner -->
        <section class="welcome-banner">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                <div>
                    <h1>Welcome, <?php echo htmlspecialchars($user_name); ?> 👋</h1>
                    <p>Track your internship deliverables, maintain deadlines, and level up your skills.</p>
                </div>
                <div>
                    <a href="add_task.php" class="btn btn-primary" style="width: auto; padding: 0.6rem 1.25rem;">+ Create New Task</a>
                </div>
            </div>
        </section>

        <!-- Smart Reminder & Overdue Alerts -->
        <?php if ($overdue_count > 0 || $today_due_count > 0): ?>
            <section style="margin-bottom: 2rem; display: flex; flex-direction: column; gap: 0.75rem;">
                <?php if ($overdue_count > 0): ?>
                    <div style="background-color: #fee2e2; border-left: 5px solid #dc2626; padding: 1rem 1.25rem; border-radius: var(--radius); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
                        <div style="color: #991b1b; font-weight: 600;">
                            ⚠️ You have <strong><?php echo $overdue_count; ?> overdue task(s)</strong> that need your immediate attention!
                        </div>
                        <a href="tasks.php?status=Pending" style="color: #b91c1c; font-weight: 700; text-decoration: underline; font-size: 0.9rem;">View Overdue &rarr;</a>
                    </div>
                <?php endif; ?>

                <?php if ($today_due_count > 0): ?>
                    <div style="background-color: #fef3c7; border-left: 5px solid #f59e0b; padding: 1rem 1.25rem; border-radius: var(--radius); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
                        <div style="color: #92400e; font-weight: 600;">
                            ⏳ You have <strong><?php echo $today_due_count; ?> task(s) due today</strong>. Plan your day to submit them on time!
                        </div>
                        <a href="tasks.php" style="color: #b45309; font-weight: 700; text-decoration: underline; font-size: 0.9rem;">View Today's Tasks &rarr;</a>
                    </div>
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <!-- Task Completion Progress Bar Card -->
        <section class="progress-card">
            <div class="progress-header">
                <h3>Overall Completion Progress</h3>
                <span class="progress-percent"><?php echo $progress_percent; ?>% Completed (<?php echo $completed_tasks; ?> of <?php echo $total_tasks; ?>)</span>
            </div>
            <div class="progress-bar-bg">
                <div class="progress-bar-fill" style="width: <?php echo $progress_percent; ?>%;"></div>
            </div>
        </section>

        <!-- Dynamic Statistics Cards Grid -->
        <section class="stats-grid">
            
            <div class="stat-card total">
                <div class="stat-icon">📁</div>
                <div class="stat-info">
                    <h3>Total Tasks</h3>
                    <p class="stat-number"><?php echo $total_tasks; ?></p>
                </div>
            </div>

            <div class="stat-card pending">
                <div class="stat-icon">⏳</div>
                <div class="stat-info">
                    <h3>Pending</h3>
                    <p class="stat-number"><?php echo $pending_tasks; ?></p>
                </div>
            </div>

            <div class="stat-card in-progress">
                <div class="stat-icon">⚙️</div>
                <div class="stat-info">
                    <h3>In Progress</h3>
                    <p class="stat-number"><?php echo $inprogress_tasks; ?></p>
                </div>
            </div>

            <div class="stat-card completed">
                <div class="stat-icon">✅</div>
                <div class="stat-info">
                    <h3>Completed</h3>
                    <p class="stat-number"><?php echo $completed_tasks; ?></p>
                </div>
            </div>

        </section>

        <!-- Quick Navigation Actions -->
        <section class="quick-actions">
            <a href="tasks.php" class="btn btn-secondary">📋 View All Tasks</a>
            <a href="export_csv.php" class="btn btn-secondary">📥 Export Tasks to CSV</a>
            <a href="profile.php" class="btn btn-secondary">👤 My Profile</a>
        </section>

        <!-- Recent Tasks List with Date-Range Filters -->
        <section class="table-container">
            <div class="task-list-header">
                <h2>Recent Task Overview</h2>
                <div class="task-filters">
                    <a href="dashboard.php?filter=all" class="filter-btn <?php echo ($filter === 'all') ? 'active' : ''; ?>">All Recent</a>
                    <a href="dashboard.php?filter=day" class="filter-btn <?php echo ($filter === 'day') ? 'active' : ''; ?>">Due Today</a>
                    <a href="dashboard.php?filter=week" class="filter-btn <?php echo ($filter === 'week') ? 'active' : ''; ?>">This Week</a>
                    <a href="dashboard.php?filter=month" class="filter-btn <?php echo ($filter === 'month') ? 'active' : ''; ?>">This Month</a>
                </div>
            </div>

            <?php if (mysqli_num_rows($recent_result) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Task Title</th>
                            <th>Category</th>
                            <th>Deadline & Alert</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Deliverables</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $today_obj = new DateTime('today');

                        while ($task = mysqli_fetch_assoc($recent_result)): 
                            $priorityClass = strtolower($task['priority']);
                            $statusClass   = strtolower(str_replace(' ', '-', $task['status']));
                            $catClass      = strtolower($task['category'] ?? 'general');
                            $task_deadline = $task['deadline'];

                            $due_obj = new DateTime($task_deadline);
                            $diff_days = (int)$today_obj->diff($due_obj)->format("%r%a");

                            $smart_badge = "";
                            if ($task['status'] !== 'Completed') {
                                if ($diff_days < 0) {
                                    $smart_badge = '<span class="badge-deadline overdue">⚠️ Overdue</span>';
                                } elseif ($diff_days === 0) {
                                    $smart_badge = '<span class="badge-deadline due-today">⏳ Due Today</span>';
                                } elseif ($diff_days <= 2) {
                                    $smart_badge = '<span class="badge-deadline due-soon">🔔 Soon</span>';
                                }
                            }
                        ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($task['title']); ?></strong>
                                    <?php if (!empty($task['description'])): ?>
                                        <div style="font-size: 0.82rem; color: var(--text-muted); max-width: 250px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                            <?php echo htmlspecialchars($task['description']); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge category-<?php echo $catClass; ?>">
                                        <?php echo htmlspecialchars($task['category'] ?? 'General'); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo date('M d, Y', strtotime($task_deadline)); ?>
                                    <?php echo $smart_badge; ?>
                                </td>
                                <td>
                                    <span class="badge priority-<?php echo $priorityClass; ?>">
                                        <?php echo htmlspecialchars($task['priority']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge status-<?php echo $statusClass; ?>">
                                        <?php echo htmlspecialchars($task['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 0.35rem; align-items: center;">
                                        <?php if (!empty($task['attachment'])): ?>
                                            <a href="uploads/<?php echo urlencode($task['attachment']); ?>" target="_blank" class="btn-action" style="background-color: #ede9fe; color: #6d28d9; padding: 0.2rem 0.45rem; font-size: 0.75rem;" title="Download Attachment">
                                                📎 File
                                            </a>
                                        <?php endif; ?>
                                        <?php if (!empty($task['submission_link'])): ?>
                                            <a href="<?php echo htmlspecialchars($task['submission_link']); ?>" target="_blank" class="btn-action" style="background-color: #e0f2fe; color: #0369a1; padding: 0.2rem 0.45rem; font-size: 0.75rem;" title="View Deliverable URL">
                                                🔗 Link
                                            </a>
                                        <?php endif; ?>
                                        <?php if (empty($task['attachment']) && empty($task['submission_link'])): ?>
                                            <span style="color: var(--text-muted); font-size: 0.8rem;">—</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <a href="edit_task.php?id=<?php echo $task['id']; ?>" class="btn-action edit">Edit</a>
                                    <a href="delete_task.php?id=<?php echo $task['id']; ?>" class="btn-action delete" data-task-title="<?php echo htmlspecialchars($task['title']); ?>">Delete</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div style="text-align: center; padding: 2.5rem 1rem; color: var(--text-muted);">
                    <p>No tasks found for the selected filter. Try another filter or create a new task.</p>
                </div>
            <?php endif; ?>

        </section>

    </main>

    <!-- Footer -->
    <footer>
        <p>&copy; 2026 Internship Task Tracker </p>
    </footer>

    <script src="js/script.js"></script>
</body>
</html>
<?php 
mysqli_stmt_close($stmt_recent); 
?>
